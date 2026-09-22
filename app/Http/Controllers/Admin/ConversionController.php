<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ConvertResourceToPdfLocally;
use App\Models\Resource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ConversionController extends Controller
{
    /**
     * Polled by the dashboard's "convert on this device" prompt to show
     * whether a local worker is currently listening, and how big the
     * backlog still is.
     */
    public function status(): JsonResponse
    {
        $heartbeat = Cache::get('conversion:local-worker:heartbeat');

        return response()->json([
            'worker_connected' => $heartbeat !== null,
            'worker_last_seen' => $heartbeat,
            'needs_conversion' => $this->backlog()->count(),
            'converting' => Resource::whereIn('conversion_status', ['pending', 'processing'])->count(),
        ]);
    }

    /**
     * The admin said "yes" to converting on this device: queue the current
     * backlog onto the local queue. Nothing actually converts until the
     * admin runs `php artisan conversion:work-local` on their own machine
     * — this only enqueues the work.
     */
    public function startLocal(): JsonResponse
    {
        $resources = $this->backlog()->get();

        foreach ($resources as $resource) {
            $resource->conversion_status = 'pending';
            $resource->conversion_error = null;
            $resource->save();

            ConvertResourceToPdfLocally::dispatch($resource->id)
                ->onQueue(config('conversion.local.queue'));
        }

        return response()->json([
            'queued' => $resources->count(),
            'command' => 'php artisan conversion:work-local',
        ]);
    }

    private function backlog()
    {
        return Resource::where('format', '!=', 'pdf')
            ->whereIn('conversion_status', ['none', 'failed']);
    }
}
