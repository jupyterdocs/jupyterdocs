<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Resource;
use App\Models\ResourceType;
use Illuminate\Support\Facades\DB;

class ContentController extends Controller
{
    public function index()
    {
        $byFormat = Resource::select('format', DB::raw('count(*) as total'))
            ->groupBy('format')
            ->orderByDesc('total')
            ->pluck('total', 'format');

        $typeCounts = Resource::select('resource_type_id', DB::raw('count(*) as total'))
            ->groupBy('resource_type_id')
            ->orderByDesc('total')
            ->pluck('total', 'resource_type_id');

        $typeNames = ResourceType::whereIn('id', $typeCounts->keys())->pluck('name', 'id');

        $byType = $typeCounts->map(fn ($total, $id) => [
            'name' => $typeNames[$id] ?? 'Unknown',
            'total' => $total,
        ])->values();

        $byStatus = Resource::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byConversion = Resource::where('format', '!=', 'pdf')
            ->select('conversion_status', DB::raw('count(*) as total'))
            ->groupBy('conversion_status')
            ->pluck('total', 'conversion_status');

        $needsConversion = Resource::where('format', '!=', 'pdf')
            ->whereIn('conversion_status', ['none', 'pending', 'processing', 'failed'])
            ->with('uploader')
            ->latest()
            ->paginate(10, ['*'], 'conversion_page');

        $topDownloaded = Resource::approved()
            ->orderByDesc('downloads_count')
            ->take(10)
            ->get();

        $recentUploads = Resource::with(['uploader', 'resourceType'])
            ->latest()
            ->take(10)
            ->get();

        $totals = [
            'total_resources' => Resource::count(),
            'total_downloads' => Download::count(),
            'total_storage_bytes' => (int) Resource::sum('file_size'),
        ];

        return view('admin.content.index', compact(
            'byFormat', 'byType', 'byStatus', 'byConversion', 'needsConversion', 'topDownloaded', 'recentUploads', 'totals'
        ));
    }
}
