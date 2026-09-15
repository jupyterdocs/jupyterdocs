<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $onlineThreshold = now()->subMinutes(5)->timestamp;

        $statusCounts = Resource::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'total_users' => User::count(),
            'online_now' => DB::table('sessions')
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', $onlineThreshold)
                ->distinct('user_id')
                ->count('user_id'),
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

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentUploads', 'failedConversions'));
    }
}
