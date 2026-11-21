<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds a Server-Timing header so browser DevTools (Network -> Timing) and HAR
 * exports show where a slow response is spent: PHP/Laravel as a whole, versus
 * database time alone. If "app" is small but the wait is long, the delay is in
 * the network or the web server in front of PHP, not in this code.
 */
class ServerTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        $queries = 0;
        $queryMs = 0.0;

        DB::listen(function ($query) use (&$queries, &$queryMs) {
            $queries++;
            $queryMs += $query->time;
        });

        $response = $next($request);

        $appMs = defined('LARAVEL_START') ? (microtime(true) - LARAVEL_START) * 1000 : null;

        $metrics = [sprintf('db;dur=%.1f;desc="%d queries"', $queryMs, $queries)];

        if ($appMs !== null) {
            array_unshift($metrics, sprintf('app;dur=%.1f', $appMs));
        }

        $response->headers->set('Server-Timing', implode(', ', $metrics), false);

        return $response;
    }
}
