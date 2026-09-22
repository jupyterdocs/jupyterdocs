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

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhereHas('course', fn (Builder $c) => $c->where('name', 'like', "%{$term}%"))
                ->orWhereHas('tags', fn (Builder $t) => $t->where('name', 'like', "%{$term}%"));
        });
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
