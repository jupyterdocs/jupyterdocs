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
        // This device's own batch (whatever its status — including a
        // document that's still just sitting in the local queue waiting
        // for a worker), plus anything the remote pipeline is actively on.
        $localBatch = Resource::where('queued_for_local_conversion', true)
            ->whereIn('conversion_status', ['pending', 'processing', 'failed'])
            ->with('queuedBy')
            ->orderBy('updated_at')
            ->get(['id', 'title', 'format', 'conversion_status', 'conversion_error', 'queued_by', 'updated_at']);

        $remoteProcessing = Resource::where('conversion_status', 'processing')
            ->where('queued_for_local_conversion', false)
            ->orderBy('updated_at')
            ->get(['id', 'title', 'format', 'updated_at']);

        $backlog = $this->backlog()
            ->with('uploader')
            ->orderBy('created_at')
            ->get(['id', 'title', 'format', 'conversion_status', 'conversion_error', 'uploader_id', 'uploader_name', 'created_at']);

        $recentlyConverted = Resource::where('conversion_status', 'done')
            ->with('queuedBy')
            ->latest('converted_at')
            ->paginate(15, ['*'], 'converted_page');

        return view('admin.conversion.index', [
            'localBatch' => $localBatch,
            'remoteProcessing' => $remoteProcessing,
            'backlog' => $backlog,
            'recentlyConverted' => $recentlyConverted,
            'localWorkerConnected' => Cache::has('conversion:local-worker:heartbeat'),
        ]);
    }

    /**
     * Polled by the conversion module to keep the live sections fresh
     * without a reload.
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
        // Drop documents that finished (either way) in an earlier batch so
        // the list doesn't grow forever; anything still failed is eligible
        // to be picked again via the backlog query below.
        Resource::where('queued_for_local_conversion', true)
            ->whereIn('conversion_status', ['done', 'failed'])
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

    /**
     * Everything that still needs converting and isn't already claimed by
     * this device's current batch: never-attempted documents, documents a
     * previous attempt failed on, and documents sitting in the remote
     * queue that haven't started yet (e.g. because the remote pipeline is
     * backed up) — all of those are "waiting", not just 'none'/'failed'.
     */
    private function backlog()
    {
        return Resource::where('format', '!=', 'pdf')
            ->where('queued_for_local_conversion', false)
            ->whereIn('conversion_status', ['none', 'pending', 'failed']);
    }
}
