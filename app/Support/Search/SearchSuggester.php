<?php

namespace App\Support\Search;

use App\Models\Course;
use App\Models\Resource;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;

/**
 * Feeds the search box's as-you-type dropdown: topics, courses and
 * document titles that start with (or contain a word starting with) what
 * has been typed so far, most useful first.
 */
class SearchSuggester
{
    /**
     * @return list<array{text: string, type: string}>
     */
    public function suggest(string $typed, int $limit = 8): array
    {
        $typed = trim((string) preg_replace('/\s+/', ' ', Tokenizer::normalize($typed)));
        // LIKE wildcards typed by a user are never meant literally here.
        $typed = str_replace(['%', '_', '\\'], '', $typed);

        if (mb_strlen($typed) < 2) {
            return [];
        }

        $matches = fn (Builder $query, string $column) => $query->where(
            fn (Builder $q) => $q->whereRaw("LOWER({$column}) LIKE ?", [$typed.'%'])
                ->orWhereRaw("LOWER({$column}) LIKE ?", ['% '.$typed.'%'])
        );

        $topics = $matches(Tag::query(), 'name')
            ->whereHas('resources', fn (Builder $r) => $r->approved())
            ->withCount(['resources' => fn (Builder $r) => $r->approved()])
            ->orderByDesc('resources_count')
            ->limit(3)
            ->pluck('name');

        $courses = $matches(Course::query(), 'name')
            ->whereHas('resources', fn (Builder $r) => $r->approved())
            ->limit(2)
            ->pluck('name');

        $titles = $matches(Resource::approved(), 'title')
            ->orderByDesc('downloads_count')
            ->limit(5)
            ->pluck('title');

        $suggestions = [];
        $seen = [];

        $add = function ($names, string $type) use (&$suggestions, &$seen) {
            foreach ($names as $name) {
                $key = mb_strtolower($name);

                if (! isset($seen[$key])) {
                    $seen[$key] = true;
                    $suggestions[] = ['text' => $name, 'type' => $type];
                }
            }
        };

        $add($topics, 'Topic');
        $add($titles, 'Document');
        $add($courses, 'Course');

        return array_slice($suggestions, 0, $limit);
    }
}
