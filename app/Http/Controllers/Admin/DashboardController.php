<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Resource;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Support\Conversion\LocalConversionBatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $onlineThreshold = now()->subMinutes(5);

        $statusCounts = Resource::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $windowDays = 30;
        $windowStart = now()->subDays($windowDays - 1)->toDateString();
        $avgDailyActiveUsers = round(
            UserActivityLog::where('activity_date', '>=', $windowStart)->count() / $windowDays,
            1
        );

        $stats = [
            'total_users' => User::count(),
            'online_now' => User::where('last_seen_at', '>=', $onlineThreshold)->count(),
            'avg_daily_active_users' => $avgDailyActiveUsers,
            'new_users_7d' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'total_resources' => Resource::count(),
            'pending_review' => $statusCounts['pending'] ?? 0,
            'approved' => $statusCounts['approved'] ?? 0,
            'rejected' => $statusCounts['rejected'] ?? 0,
            'needs_conversion' => Resource::where('format', '!=', 'pdf')
                ->whereIn('conversion_status', ['none', 'failed'])
                ->count(),
            'converting' => Resource::whereIn('conversion_status', ['pending', 'processing'])->count(),
            'total_downloads' => Download::count(),
        ];

        $recentUsers = User::latest()->take(5)->get();

        $recentUploads = Resource::with(['uploader', 'resourceType'])
            ->latest()
            ->take(5)
            ->get();

        $failedConversions = Resource::where('conversion_status', 'failed')
            ->with('uploader')
            ->latest('converted_at')
            ->take(5)
            ->get();

        // Server-side truth for the "convert on this device" panel, so it
        // renders correctly on first load — a reload or a tab switch never
        // loses the running batch or its command the way client-only state
        // tracked purely in JS would.
        $localBatch = LocalConversionBatch::current();
        $localWorkerConnected = Cache::has('conversion:local-worker:heartbeat');

        return view('admin.dashboard', compact(
            'stats', 'recentUsers', 'recentUploads', 'failedConversions', 'localBatch', 'localWorkerConnected'
        ));
    }
}
