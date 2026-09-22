<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ConvertResourceToPdfLocally;
use App\Models\Resource;
use App\Support\Conversion\LocalConversionBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ConversionController extends Controller
{
    /**
     * The conversion module: what's converting right now and by whom,
     * what's waiting so an admin can pick what goes next, and a history of
     * what's already been converted and which driver (CloudConvert,
     * Gotenberg, or this device) did it.
     */
    public function index()
    {
        $processing = Resource::where('conversion_status', 'processing')
            ->with('queuedBy')
            ->orderBy('updated_at')
            ->get(['id', 'title', 'format', 'queued_for_local_conversion', 'queued_by', 'updated_at']);

        $backlog = $this->backlog()
            ->with('uploader')
            ->orderBy('created_at')
            ->get(['id', 'title', 'format', 'conversion_status', 'conversion_error', 'uploader_id', 'uploader_name', 'created_at']);

        $recentlyConverted = Resource::where('conversion_status', 'done')
            ->with('queuedBy')
            ->latest('converted_at')
            ->paginate(15, ['*'], 'converted_page');

        return view('admin.conversion.index', [
            'processing' => $processing,
            'backlog' => $backlog,
            'recentlyConverted' => $recentlyConverted,
            'localWorkerConnected' => Cache::has('conversion:local-worker:heartbeat'),
        ]);
    }

    /**
     * Polled by the conversion module (and the dashboard's slim summary)
     * to keep the "processing" and "waiting" lists live without a reload.
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
     * Queue documents onto the local queue for this device to convert.
     * When the admin hand-picked specific documents (and an order) in the
     * conversion module, only those are queued, in exactly that order —
     * that choice is what decides which one the worker picks up next.
     * With no selection, the whole current backlog is queued, same as
     * before selection existed.
     */
    public function startLocal(Request $request): JsonResponse
    {
        // Drop finished documents from an earlier batch so the list doesn't
        // grow forever; anything still failed gets swept back in below.
        Resource::where('queued_for_local_conversion', true)
            ->where('conversion_status', 'done')
            ->update(['queued_for_local_conversion' => false]);

        $selectedIds = collect($request->input('resource_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $eligible = $this->backlog()->get()->keyBy('id');

        $resources = $selectedIds->isNotEmpty()
            ? $selectedIds->map(fn ($id) => $eligible->get($id))->filter()->values()
            : $eligible->values();

        foreach ($resources as $resource) {
            $resource->conversion_status = 'pending';
            $resource->conversion_error = null;
            $resource->queued_for_local_conversion = true;
            $resource->queued_by = $request->user()->id;
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
