<?php

namespace App\Observers;

use App\Models\Resource;

class ResourceObserver
{
    /**
     * uploads_count powers the download-unlock threshold and increments the
     * moment a resource is uploaded — it deliberately does not wait for
     * admin moderation, so uploaders get credit immediately.
     */
    public function created(Resource $resource): void
    {
        $resource->uploader?->increment('uploads_count');
    }

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
        $resource->uploader?->decrement('uploads_count');

        if ($resource->status === 'approved') {
            $resource->uploader?->decrement('approved_uploads_count');
        }
    }
}
