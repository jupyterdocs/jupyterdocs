<?php

namespace App\Models;

use App\Observers\ResourceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(ResourceObserver::class)]
class Resource extends Model
{
    use HasFactory, SoftDeletes;

    public const UPLOADS_PER_DOWNLOAD = 2;

    protected $fillable = [
        'uploader_id',
        'uploader_name',
        'uploader_email',
        'resource_type_id',
        'university_id',
        'course_id',
        'title',
        'description',
        'file_path',
        'thumbnail_path',
        'file_size',
        'format',
        'pages',
        'status',
        'rejected_reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'converted_at' => 'datetime',
        'queued_for_local_conversion' => 'boolean',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // The admin who queued this document for local conversion, if any.
    public function queuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'queued_by');
    }

    public function resourceType(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class);
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Approved documents on the same topic or unit, best match first.
     *
     * Relevance comes from the course (the unit) and from shared tags, which
     * are generated from the title and description; multi-word tags like
     * "operating systems" are far more specific than single words so they
     * weigh more. A document must clear a topical threshold to appear at all,
     * so sharing only a resource type or university never qualifies — those
     * only break ties between topical matches.
     */
    public function relatedResources(int $limit = 6): Collection
    {
        $tags = $this->tags;
        $tagIds = $tags->pluck('id');

        if (! $this->course_id && $tagIds->isEmpty()) {
            return new Collection;
        }

        $candidates = static::approved()
            ->where('id', '!=', $this->id)
            ->where(function (Builder $q) use ($tagIds) {
                if ($this->course_id) {
                    $q->orWhere('course_id', $this->course_id);
                }

                if ($tagIds->isNotEmpty()) {
                    $q->orWhereHas('tags', fn (Builder $t) => $t->whereIn('tags.id', $tagIds));
                }
            })
            ->with(['resourceType', 'tags:id,name'])
            ->latest()
            ->limit(60)
            ->get();

        $mine = $tags->keyBy('id');

        return $candidates
            ->map(function (self $candidate) use ($mine) {
                $topical = $this->course_id && $candidate->course_id === $this->course_id ? 6.0 : 0.0;

                foreach ($candidate->tags as $tag) {
                    if ($mine->has($tag->id)) {
                        $topical += str_contains($tag->name, ' ') ? 3.0 : 1.5;
                    }
                }

                $candidate->setAttribute('relevance', $topical);

                if ($topical >= 2.0) {
                    $candidate->relevance += ($candidate->university_id && $candidate->university_id === $this->university_id ? 1.0 : 0.0)
                        + ($candidate->resource_type_id === $this->resource_type_id ? 0.5 : 0.0);
                }

                return $candidate;
            })
            ->filter(fn (self $candidate) => $candidate->relevance >= 2.0)
            ->sortByDesc('relevance')
            ->take($limit)
            ->values();
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ResourceVote::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ResourceReport::class);
    }

    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_resources')->withTimestamps();
    }

    /**
     * Eager-load like/dislike tallies as likes_count / dislikes_count.
     */
    public function scopeWithVoteCounts(Builder $query): Builder
    {
        return $query->withCount([
            'votes as likes_count' => fn (Builder $q) => $q->where('value', ResourceVote::LIKE),
            'votes as dislikes_count' => fn (Builder $q) => $q->where('value', ResourceVote::DISLIKE),
        ]);
    }

    /**
     * Like/dislike share as whole percentages that always add up to 100,
     * or nulls when nobody has voted yet.
     *
     * @return array{likes: int, dislikes: int, like_percent: ?int, dislike_percent: ?int}
     */
    public function voteSummary(): array
    {
        $likes = (int) ($this->likes_count ?? $this->votes()->where('value', ResourceVote::LIKE)->count());
        $dislikes = (int) ($this->dislikes_count ?? $this->votes()->where('value', ResourceVote::DISLIKE)->count());
        $total = $likes + $dislikes;

        $likePercent = $total ? (int) round($likes / $total * 100) : null;

        return [
            'likes' => $likes,
            'dislikes' => $dislikes,
            'like_percent' => $likePercent,
            'dislike_percent' => $total ? 100 - $likePercent : null,
        ];
    }

    public function isInteractableBy(?User $user): bool
    {
        return $user !== null && $this->status === 'approved';
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function uploaderDisplayName(): string
    {
        return $this->uploader?->name ?? $this->uploader_name ?? 'Anonymous';
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail_path
            ? Storage::disk(config('filesystems.thumbnail_disk'))->url($this->thumbnail_path)
            : null;
    }

    public function hasPdfPreview(): bool
    {
        return $this->format === 'pdf' || $this->conversion_status === 'done';
    }

    public function previewSourcePath(): ?string
    {
        if ($this->format === 'pdf') {
            return $this->file_path;
        }

        return $this->converted_pdf_path;
    }

    public function isDownloadableBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->status !== 'approved') {
            return $user->id === $this->uploader_id || $user->isAdmin();
        }

        return $user->id === $this->uploader_id
            || $user->isAdmin()
            || $user->canDownload()
            // Already paid for once, so fetching it again is free.
            || $user->downloads()->where('resource_id', $this->id)->exists();
    }

    public function isViewableBy(?User $user, ?array $guestUploadIds = null): bool
    {
        if ($this->status === 'approved') {
            return true;
        }

        if ($user) {
            return $user->id === $this->uploader_id || $user->isAdmin();
        }

        return in_array($this->id, $guestUploadIds ?? [], true);
    }

    public function isPreviewableBy(?User $user): bool
    {
        return $user && ($user->isAdmin() || $user->id === $this->uploader_id);
    }

    public function pagesLabel(): ?string
    {
        if (! $this->pages) {
            return null;
        }

        $unit = in_array($this->format, ['xls', 'xlsx']) ? 'sheet' : 'page';

        return $this->pages.' '.\Illuminate\Support\Str::plural($unit, $this->pages);
    }
}
