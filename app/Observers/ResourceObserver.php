<?php

namespace App\Observers;

use App\Models\Resource;
use App\Support\Search\ResourceSearchIndexer;
use Throwable;

class ResourceObserver
{
    private const SEARCHABLE = ['title', 'description', 'course_id', 'university_id', 'resource_type_id', 'format'];

    /**
     * uploads_count powers the download-unlock threshold and increments the
     * moment a resource is uploaded — it deliberately does not wait for
     * admin moderation, so uploaders get credit immediately.
     */
    public function created(Resource $resource): void
    {
        $resource->uploader?->increment('uploads_count');

        $this->reindex($resource);
    }

    public function updated(Resource $resource): void
    {
        if ($resource->wasChanged(self::SEARCHABLE)) {
            $this->reindex($resource);
        }

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

    /**
     * Search indexing must never be the reason an upload fails, so a
     * problem is reported and swallowed; `php artisan search:reindex`
     * repairs the index afterwards.
     */
    private function reindex(Resource $resource): void
    {
        try {
            app(ResourceSearchIndexer::class)->index($resource);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
