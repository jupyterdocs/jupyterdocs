<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceReport extends Model
{
    public const REASONS = [
        'copyright' => 'Copyright infringement',
        'inappropriate' => 'Inappropriate or offensive content',
        'spam' => 'Spam or misleading',
        'wrong_info' => 'Wrong title, course or description',
        'broken' => 'File is broken or unreadable',
        'other' => 'Other',
    ];

    protected $fillable = ['user_id', 'resource_id', 'reason', 'details', 'status', 'reviewed_by', 'reviewed_at'];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? ucfirst($this->reason);
    }
}
