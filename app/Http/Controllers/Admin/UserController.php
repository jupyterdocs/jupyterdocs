<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Support\TimeSeries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $onlineThreshold = now()->subMinutes(5);

        $users = User::withCount('resources', 'downloads')
            ->orderByDesc('created_at')
            ->paginate(20);

        $onlineCount = User::where('last_seen_at', '>=', $onlineThreshold)->count();
        $adminCount = User::where('role', 'admin')->count();

        $registrationsByYear = TimeSeries::fillYears(
            User::select(DB::raw('EXTRACT(YEAR FROM created_at) as year'), DB::raw('count(*) as total'))
                ->groupBy('year')
                ->get()
        );

        $windowDays = 30;
        $windowStart = now()->subDays($windowDays - 1)->toDateString();
        $activeUserDaysInWindow = UserActivityLog::where('activity_date', '>=', $windowStart)->count();
        $avgDailyActiveUsers = round($activeUserDaysInWindow / $windowDays, 1);

        return view('admin.users.index', compact(
            'users', 'onlineCount', 'adminCount', 'registrationsByYear', 'avgDailyActiveUsers'
        ));
    }

    public function show(User $user)
    {
        $user->loadCount('resources', 'downloads');

        $uploads = $user->resources()
            ->with('resourceType')
            ->latest()
            ->paginate(10, ['*'], 'uploads_page');

        $downloads = $user->downloads()
            ->with('resource')
            ->latest('downloaded_at')
            ->paginate(10, ['*'], 'downloads_page');

        $activityByDay = $user->activityLogs()
            ->orderByDesc('activity_date')
            ->take(30)
            ->get()
            ->sortBy('activity_date')
            ->values();

        return view('admin.users.show', compact('user', 'uploads', 'downloads', 'activityByDay'));
    }

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:student,admin'],
        ]);

        if ($user->id === auth()->id()) {
            return back()->with('status', "You can't change your own role.");
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('status', "{$user->name}'s role updated to {$validated['role']}.");
    }
}
