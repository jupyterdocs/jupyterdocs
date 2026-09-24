<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs one row per real page view (guests and signed-in users alike) for the
 * admin Traffic report. Runs after the response is sent and can never break
 * a page: any failure is swallowed.
 */
class RecordPageVisit
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|uptime|curl|wget|python|http-client|headless|lighthouse|scanner/i';

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            if (! $this->shouldRecord($request, $response)) {
                return;
            }

            $user = $request->user();
            $today = now()->toDateString();

            // Members are counted by account, guests by an anonymous
            // per-day fingerprint that changes every midnight.
            $fingerprint = $user
                ? 'user:'.$user->id
                : 'guest:'.$this->ip($request).'|'.substr((string) $request->userAgent(), 0, 200);

            PageVisit::create([
                'visit_date' => $today,
                'visitor_hash' => hash_hmac('sha256', $fingerprint.'|'.$today, (string) config('app.key')),
                'user_id' => $user?->id,
                'country_code' => $this->countryCode($request),
                'region' => $this->header($request, ['CF-Region', 'X-Vercel-IP-Country-Region']),
                'city' => $this->header($request, ['CF-IPCity', 'X-Vercel-IP-City']),
                'path' => '/'.ltrim(mb_substr($request->path(), 0, 250), '/'),
            ]);
        } catch (\Throwable) {
            // Analytics must never take the site down.
        }
    }

    private function shouldRecord(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->prefetch()) {
            return false;
        }

        if ($response->getStatusCode() !== 200 || $response instanceof \Symfony\Component\HttpFoundation\StreamedResponse
            || $response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            return false;
        }

        if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return false;
        }

        if ($request->is('admin', 'admin/*', 'up', 'sitemap.xml', 'search/suggest')) {
            return false;
        }

        $agent = (string) $request->userAgent();

        return $agent !== '' && ! preg_match(self::BOT_PATTERN, $agent);
    }

    private function ip(Request $request): string
    {
        return (string) ($request->header('CF-Connecting-IP') ?: $request->ip());
    }

    /**
     * The country comes from the CDN in front of the site (Cloudflare and
     * similar add it for free); no lookup service or IP database is used.
     */
    private function countryCode(Request $request): ?string
    {
        $code = strtoupper((string) $this->header($request, [
            'CF-IPCountry', 'CloudFront-Viewer-Country', 'X-Vercel-IP-Country', 'X-Country-Code',
        ]));

        // Cloudflare uses XX (unknown) and T1 (Tor).
        return preg_match('/^[A-Z]{2}$/', $code) && ! in_array($code, ['XX', 'T1'], true) ? $code : null;
    }

    private function header(Request $request, array $names): ?string
    {
        foreach ($names as $name) {
            if ($value = trim((string) $request->header($name))) {
                return mb_substr(rawurldecode($value), 0, 100);
            }
        }

        return null;
    }
}
