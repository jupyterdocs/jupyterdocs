<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceRequestVote extends Model
{
    protected $fillable = ['user_id', 'resource_request_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resourceRequest(): BelongsTo
    {
        return $this->belongsTo(ResourceRequest::class);
    }
}
