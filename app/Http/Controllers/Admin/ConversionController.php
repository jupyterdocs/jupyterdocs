<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ConvertResourceToPdfLocally;
use App\Models\Resource;
use App\Support\Conversion\LocalConversionBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ConversionController extends Controller
{
    /**
     * Polled by the dashboard's "convert on this device" panel to show
     * whether a local worker is currently listening, and the live status
     * of every document in the current local batch. Driven entirely by
     * the database, not the browser, so it reads the same regardless of
     * reloads, tab switches or a different admin opening the dashboard.
     */
    public function status(): JsonResponse
    {
        $heartbeat = Cache::get('conversion:local-worker:heartbeat');

        return response()->json([
            'worker_connected' => $heartbeat !== null,
            'worker_last_seen' => $heartbeat,
            'needs_conversion' => $this->backlog()->count(),
            'converting' => Resource::whereIn('conversion_status', ['pending', 'processing'])->count(),
            'batch' => LocalConversionBatch::current(),
        ]);
    }

    /**
     * The admin said "yes" to converting on this device: queue the current
     * backlog onto the local queue. Nothing actually converts until a
     * local worker is listening — either one started by hand with
     * `php artisan conversion:work-local`, or one already running quietly
     * in the background because `conversion:install-local-worker` was set
     * up earlier.
     */
    public function startLocal(): JsonResponse
    {
        // Drop finished documents from an earlier batch so the list doesn't
        // grow forever; anything still failed gets swept back in below.
        Resource::where('queued_for_local_conversion', true)
            ->where('conversion_status', 'done')
            ->update(['queued_for_local_conversion' => false]);

        $resources = $this->backlog()->get();

        foreach ($resources as $resource) {
            $resource->conversion_status = 'pending';
            $resource->conversion_error = null;
            $resource->queued_for_local_conversion = true;
            $resource->save();

            ConvertResourceToPdfLocally::dispatch($resource->id)
                ->onQueue(config('conversion.local.queue'));
        }

        return response()->json([
            'queued' => $resources->count(),
            'command' => 'php artisan conversion:work-local',
            'install_command' => 'php artisan conversion:install-local-worker',
            'batch' => LocalConversionBatch::current(),
        ]);
    }

    private function backlog()
    {
        return Resource::where('format', '!=', 'pdf')
            ->whereIn('conversion_status', ['none', 'failed']);
    }
}
