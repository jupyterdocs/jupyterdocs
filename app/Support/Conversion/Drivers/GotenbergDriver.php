<?php

namespace App\Support\Conversion\Drivers;

use App\Models\Resource;
use App\Support\Conversion\ConversionDriver;
use App\Support\Conversion\ConversionResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Talks to self-hosted Gotenberg (LibreOffice-backed) conversion servers.
 * Phase 1: config('conversion.gotenberg.urls') is empty, so this driver is
 * a deliberate no-op. Phase 2: once VPS are provisioned and their URLs are
 * added to GOTENBERG_URLS, this starts working with no other code change.
 */
class GotenbergDriver implements ConversionDriver
{
    public function convert(Resource $resource): ConversionResult
    {
        $urls = config('conversion.gotenberg.urls');

        if (empty($urls)) {
            return ConversionResult::failure('No Gotenberg servers configured.');
        }

        $sourceDisk = Storage::disk(config('filesystems.resource_disk'));
        $filename = $resource->title.'.'.$resource->format;
        $contents = $sourceDisk->get($resource->file_path);

        // Try each configured VPS in turn (basic failover / round-robin
        // across the free-tier instances) — first success wins.
        $shuffled = collect($urls)->shuffle();

        foreach ($shuffled as $baseUrl) {
            try {
                $request = Http::timeout(config('conversion.gotenberg.timeout'))
                    ->attach('files', $contents, $filename);

                if ($secret = config('conversion.gotenberg.shared_secret')) {
                    $request = $request->withHeaders(['X-Gotenberg-Secret' => $secret]);
                }

                $response = $request->post(rtrim($baseUrl, '/').'/forms/libreoffice/convert');

                if ($response->successful()) {
                    return ConversionResult::success('gotenberg', $response->body());
                }

                Log::warning('Gotenberg conversion attempt failed', [
                    'resource_id' => $resource->id,
                    'url' => $baseUrl,
                    'status' => $response->status(),
                ]);
            } catch (Throwable $e) {
                Log::warning('Gotenberg conversion attempt errored', [
                    'resource_id' => $resource->id,
                    'url' => $baseUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ConversionResult::failure('All configured Gotenberg servers failed or were unreachable.');
    }
}
