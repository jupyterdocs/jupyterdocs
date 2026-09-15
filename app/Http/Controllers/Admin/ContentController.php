<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Support\TimeSeries;
use Illuminate\Support\Facades\DB;

class ContentController extends Controller
{
    public function index()
    {
        $byFormat = Resource::select(
                'format',
                DB::raw('count(*) as total'),
                DB::raw('coalesce(sum(file_size), 0) as storage_bytes')
            )
            ->groupBy('format')
            ->orderByDesc('total')
            ->get()
            ->keyBy('format');

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

        $dailyUploads = TimeSeries::fillDays(
            Resource::select(DB::raw("TO_CHAR(created_at, 'YYYY-MM-DD') as day"), DB::raw('count(*) as total'))
                ->where('created_at', '>=', now()->subDays(29)->startOfDay())
                ->groupBy('day')
                ->pluck('total', 'day')
        );

        $monthlyUploads = TimeSeries::fillMonths(
            Resource::select(DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"), DB::raw('count(*) as total'))
                ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
                ->groupBy('month')
                ->pluck('total', 'month')
        );

        $yearlyUploads = TimeSeries::fillYears(
            Resource::select(DB::raw('EXTRACT(YEAR FROM created_at) as year'), DB::raw('count(*) as total'))
                ->groupBy('year')
                ->get()
        );

        return view('admin.content.index', compact(
            'byFormat', 'byType', 'byStatus', 'byConversion', 'needsConversion', 'topDownloaded', 'recentUploads',
            'totals', 'dailyUploads', 'monthlyUploads', 'yearlyUploads'
        ));
    }
}
