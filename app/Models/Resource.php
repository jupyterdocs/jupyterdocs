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

    public const MIN_UPLOADS_TO_DOWNLOAD = 3;

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
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
            ? Storage::disk('public')->url($this->thumbnail_path)
            : null;
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
            || $user->approved_uploads_count >= self::MIN_UPLOADS_TO_DOWNLOAD;
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
