<?php

namespace App\Support\Conversion;

use App\Models\Resource;

/**
 * The set of documents currently assigned to an admin's "convert on this
 * device" batch — shared by the dashboard (so the panel renders correctly
 * on first load, surviving reloads/tab switches) and the status endpoint
 * it polls afterwards.
 */
class LocalConversionBatch
{
    /**
     * @return list<array{id: int, title: string, format: string, status: string, error: ?string}>
     */
    public static function current(): array
    {
        return Resource::where('queued_for_local_conversion', true)
            ->orderBy('created_at')
            ->get(['id', 'title', 'format', 'conversion_status', 'conversion_error'])
            ->map(fn (Resource $resource) => [
                'id' => $resource->id,
                'title' => $resource->title,
                'format' => $resource->format,
                'status' => $resource->conversion_status,
                'error' => $resource->conversion_error,
            ])
            ->all();
    }
}
