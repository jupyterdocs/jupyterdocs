<?php

namespace App\Http\Middleware;

use App\Models\UserActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Requests further apart than this are treated as separate visits
     * (the gap is not counted as active time).
     */
    private const IDLE_GAP_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if ($user) {
            $now = now();
            $lastSeen = $user->last_seen_at;

            if ($lastSeen) {
                $gap = $lastSeen->diffInSeconds($now);

                if ($gap > 0 && $gap <= self::IDLE_GAP_SECONDS) {
                    // firstOrCreate() can't be used here: the search array is
                    // compared as a raw string against a "date"-cast column
                    // that's stored with a time component, so it never
                    // matches and a duplicate insert is attempted every time.
                    $log = UserActivityLog::where('user_id', $user->id)
                        ->whereDate('activity_date', $now->toDateString())
                        ->first();

                    if (! $log) {
                        try {
                            $log = UserActivityLog::create([
                                'user_id' => $user->id,
                                'activity_date' => $now->toDateString(),
                                'seconds_active' => 0,
                            ]);
                        } catch (\Illuminate\Database\QueryException) {
                            // Another concurrent request just created it first.
                            $log = UserActivityLog::where('user_id', $user->id)
                                ->whereDate('activity_date', $now->toDateString())
                                ->first();
                        }
                    }

                    $log?->increment('seconds_active', $gap);
                }
            }

            $user->forceFill(['last_seen_at' => $now])->saveQuietly();
        }

        return $response;
    }
}
