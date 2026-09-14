<?php

namespace App\Observers;

use App\Models\Resource;

class ResourceObserver
{
    public function updated(Resource $resource): void
    {
        if (! $resource->wasChanged('status')) {
            return;
        }

        $original = $resource->getOriginal('status');
        $current = $resource->status;

        if ($current === 'approved' && $original !== 'approved') {
            $resource->uploader?->increment('approved_uploads_count');
        } elseif ($original === 'approved' && $current !== 'approved') {
            $resource->uploader?->decrement('approved_uploads_count');
        }
    }

    public function deleted(Resource $resource): void
    {
        if ($resource->status === 'approved') {
            $resource->uploader?->decrement('approved_uploads_count');
        }
    }
}
