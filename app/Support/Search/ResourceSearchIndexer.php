<?php

namespace App\Support\Search;

use App\Models\Resource;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the resource_search_terms inverted index in step with the
 * resources table. Each document is broken into words per field (title,
 * tags, course, ...) so the search engine can weigh where a word matched.
 */
class ResourceSearchIndexer
{
    public const TITLE = 1;

    public const TAG = 2;

    public const COURSE = 3;

    public const UNIVERSITY = 4;

    public const TYPE = 5;

    public const DESCRIPTION = 6;

    public const FORMAT = 7;

    /** Fields that hold curated words (used as the spelling vocabulary). */
    public const VOCABULARY_FIELDS = [self::TITLE, self::TAG, self::COURSE, self::UNIVERSITY];

    public function index(Resource $resource): void
    {
        $rows = $this->rowsFor($resource);

        DB::transaction(function () use ($resource, $rows) {
            DB::table('resource_search_terms')->where('resource_id', $resource->id)->delete();

            foreach (array_chunk($rows, 400) as $chunk) {
                DB::table('resource_search_terms')->insert($chunk);
            }
        });
    }

    public function remove(int $resourceId): void
    {
        DB::table('resource_search_terms')->where('resource_id', $resourceId)->delete();
    }

    /**
     * Rebuild the whole index. Returns how many documents were indexed.
     */
    public function reindexAll(?callable $progress = null): int
    {
        $count = 0;

        Resource::query()->chunkById(200, function ($resources) use (&$count, $progress) {
            foreach ($resources as $resource) {
                $this->index($resource);
                $count++;
            }

            if ($progress) {
                $progress($count);
            }
        });

        return $count;
    }

    /**
     * @return list<array{resource_id: int, term: string, field: int, hits: int}>
     */
    private function rowsFor(Resource $resource): array
    {
        // Always re-read the relations: tags are attached *after* the
        // resource row is created, so a cached empty collection would be
        // stale.
        $resource->load(['tags', 'course', 'university', 'resourceType']);

        $fields = [
            self::TITLE => $resource->title,
            self::TAG => $resource->tags->pluck('name')->implode(' '),
            self::COURSE => trim($resource->course?->name.' '.$resource->course?->code),
            self::UNIVERSITY => $resource->university?->name,
            self::TYPE => $resource->resourceType?->name,
            self::DESCRIPTION => $resource->description,
            self::FORMAT => $resource->format,
        ];

        $rows = [];

        foreach ($fields as $field => $text) {
            foreach (Tokenizer::indexTerms($text) as $term => $hits) {
                $rows[] = [
                    'resource_id' => $resource->id,
                    'term' => (string) $term,
                    'field' => $field,
                    'hits' => min($hits, 65000),
                ];
            }
        }

        return $rows;
    }
}
