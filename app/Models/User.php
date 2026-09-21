<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const FREE_DOWNLOAD_WELCOME = 'Welcome to JupyterDocs! You have 1 free download. After that, every 2 documents you upload earns you another download.';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'google_id',
        'deactivated_at',
        'uploads_count',
        'downloads_used',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_seen_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'downloads_used' => 'integer',
            'uploads_count' => 'integer',
        ];
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class, 'uploader_id');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(UserActivityLog::class);
    }

    public function savedResources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'saved_resources')->withTimestamps();
    }

    public function resourceVotes(): HasMany
    {
        return $this->hasMany(ResourceVote::class);
    }

    public function resourceReports(): HasMany
    {
        return $this->hasMany(ResourceReport::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gte(now()->subMinutes(5));
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    public function totalActiveSeconds(): int
    {
        return (int) $this->activityLogs()->sum('seconds_active');
    }

    public function formattedTotalActiveTime(): string
    {
        $seconds = $this->totalActiveSeconds();

        if ($seconds < 60) {
            return $seconds.'s';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }

    /** 1 free download, plus 1 for every UPLOADS_PER_DOWNLOAD uploads. */
    public function downloadAllowance(): int
    {
        return 1 + intdiv($this->uploads_count, Resource::UPLOADS_PER_DOWNLOAD);
    }

    public function downloadsRemaining(): int
    {
        return max(0, $this->downloadAllowance() - $this->downloads_used);
    }

    public function hasFreeDownload(): bool
    {
        return $this->downloads_used === 0;
    }

    public function canDownload(): bool
    {
        return $this->downloadsRemaining() > 0;
    }

    public function uploadsNeededToUnlockDownloads(): int
    {
        return $this->canDownload()
            ? 0
            : $this->downloads_used * Resource::UPLOADS_PER_DOWNLOAD - $this->uploads_count;
    }
}
