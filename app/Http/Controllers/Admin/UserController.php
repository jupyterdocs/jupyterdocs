<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $onlineThreshold = now()->subMinutes(5)->timestamp;

        $onlineUserIds = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $onlineThreshold)
            ->pluck('user_id')
            ->unique();

        $lastSeen = DB::table('sessions')
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
            ->groupBy('user_id')
            ->pluck('last_activity', 'user_id');

        $users = User::withCount('resources', 'downloads')
            ->orderByDesc('created_at')
            ->paginate(20);

        $adminCount = User::where('role', 'admin')->count();

        return view('admin.users.index', compact('users', 'onlineUserIds', 'lastSeen', 'adminCount'));
    }

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:student,lecturer,admin'],
        ]);

        if ($user->id === auth()->id()) {
            return back()->with('status', "You can't change your own role.");
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('status', "{$user->name}'s role updated to {$validated['role']}.");
    }
}
