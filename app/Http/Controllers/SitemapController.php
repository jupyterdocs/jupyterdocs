<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), function () {
            $urls = [
                ['loc' => url('/'), 'changefreq' => 'daily', 'priority' => '1.0'],
                ['loc' => route('resources.index'), 'changefreq' => 'hourly', 'priority' => '0.9'],
                ['loc' => route('resources.create'), 'changefreq' => 'monthly', 'priority' => '0.5'],
            ];

            Resource::query()
                ->approved()
                ->orderBy('id')
                ->select(['id', 'updated_at'])
                ->chunk(1000, function ($resources) use (&$urls) {
                    foreach ($resources as $resource) {
                        $urls[] = [
                            'loc' => route('resources.show', $resource),
                            'lastmod' => $resource->updated_at?->toAtomString(),
                            'changefreq' => 'weekly',
                            'priority' => '0.8',
                        ];
                    }
                });

            return view('sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
