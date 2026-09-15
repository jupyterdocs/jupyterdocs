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
                    $log = UserActivityLog::firstOrCreate(
                        ['user_id' => $user->id, 'activity_date' => $now->toDateString()],
                        ['seconds_active' => 0]
                    );

                    $log->increment('seconds_active', $gap);
                }
            }

            $user->forceFill(['last_seen_at' => $now])->saveQuietly();
        }

        return $response;
    }
}
