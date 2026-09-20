<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Derives searchable tags from a resource's title and description so
 * uploaders never have to think up their own — the title carries the
 * strongest topical signal so its words are weighted higher than the
 * description's, and multi-word phrases (bigrams) are kept alongside single
 * words since people search "operating systems" as a phrase, not "operating"
 * and "systems" separately.
 */
class TagGenerator
{
    private const STOPWORDS = [
        'the', 'a', 'an', 'and', 'or', 'but', 'of', 'to', 'in', 'on', 'for',
        'with', 'as', 'by', 'at', 'from', 'is', 'are', 'was', 'were', 'be',
        'been', 'being', 'this', 'that', 'these', 'those', 'it', 'its',
        'into', 'about', 'over', 'after', 'before', 'between', 'through',
        'during', 'above', 'below', 'up', 'down', 'out', 'off', 'again',
        'further', 'then', 'once', 'here', 'there', 'when', 'where', 'why',
        'how', 'all', 'any', 'both', 'each', 'few', 'more', 'most', 'other',
        'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so',
        'than', 'too', 'very', 'can', 'will', 'just', 'should', 'now', 'you',
        'your', 'i', 'we', 'our', 'they', 'their', 'he', 'she', 'his', 'her',
        'chapter', 'chapters', 'notes', 'note', 'edition', 'part', 'parts',
        'introduction', 'overview', 'comprehensive', 'textbook', 'textbooks',
        'covers', 'cover', 'covering', 'includes', 'including', 'include',
        'ideal', 'ideally', 'designed', 'provides', 'provide', 'providing',
        'useful', 'helps', 'help', 'helpful', 'students', 'student',
        'topics', 'topic', 'worked', 'examples', 'example', 'preparing',
        'prepare', 'prepared', 'undergraduate', 'graduate', 'understanding',
        'understand', 'learn', 'learning', 'guide', 'guides', 'book',
        'books', 'study', 'studies', 'material', 'materials', 'content',
        'contents', 'according', 'using', 'use', 'used', 'well', 'good',
        'best', 'complete', 'detailed', 'everything', 'need', 'needed',
    ];

    /**
     * @return list<string>
     */
    public static function generate(string $title, string $description, int $count = 8): array
    {
        // Acronyms written in ALL CAPS in the original text (e.g. "IT",
        // "SQL", "API") are exempt from the stopword list — otherwise "IT"
        // is indistinguishable from the pronoun "it" once lowercased.
        $acronyms = array_merge(self::extractAcronyms($title), self::extractAcronyms($description));
        $meaningful = fn (string $word) => strlen($word) >= 2 && ! is_numeric($word)
            && (! in_array($word, self::STOPWORDS, true) || in_array($word, $acronyms, true));

        $titleTokens = self::tokenize($title);
        $descriptionTokens = self::tokenize($description);

        $scores = [];

        // Title words count 3x as much as description words.
        foreach (self::withCounts(array_filter($titleTokens, $meaningful)) as $word => $n) {
            $scores[$word] = ($scores[$word] ?? 0) + $n * 3;
        }

        foreach (self::withCounts(array_filter($descriptionTokens, $meaningful)) as $word => $n) {
            $scores[$word] = ($scores[$word] ?? 0) + $n;
        }

        // Bigrams (adjacent word pairs) capture real search phrases like
        // "linear algebra" or "operating systems" that single words lose.
        // Built from the full, un-filtered token list so a phrase like
        // "IT professionals" (adjacent in the original text) isn't wrongly
        // merged with an unrelated word once a stopword between them
        // (e.g. "for") is stripped out.
        foreach (self::bigrams($titleTokens, $meaningful) as $phrase => $n) {
            $scores[$phrase] = ($scores[$phrase] ?? 0) + $n * 4;
        }

        foreach (self::bigrams($descriptionTokens, $meaningful) as $phrase => $n) {
            $scores[$phrase] = ($scores[$phrase] ?? 0) + $n;
        }

        arsort($scores);

        $tags = [];

        foreach (array_keys($scores) as $candidate) {
            // Skip a candidate that's already fully contained in a
            // higher-scored phrase we've kept (e.g. don't keep both
            // "algebra" and "linear algebra").
            $alreadyCovered = collect($tags)->contains(fn ($tag) => str_contains(" {$tag} ", " {$candidate} "));

            if ($alreadyCovered) {
                continue;
            }

            // The reverse case: a shorter word was kept first (it scored
            // higher alone) but this longer phrase — kept later — fully
            // contains it, so the short version is now redundant.
            $tags = array_values(array_filter(
                $tags,
                fn ($tag) => ! str_contains(" {$candidate} ", " {$tag} ")
            ));

            $tags[] = $candidate;

            if (count($tags) >= $count) {
                break;
            }
        }

        return array_map(fn ($tag) => Str::title($tag), $tags);
    }

    /**
     * Full token list, punctuation stripped but stopwords and short words
     * kept — needed so bigrams can tell which words were truly adjacent in
     * the original text.
     *
     * @return list<string>
     */
    private static function tokenize(string $text): array
    {
        $text = strtolower(strip_tags($text));
        $text = preg_replace('/[^a-z0-9\s-]/', ' ', $text) ?? '';

        return array_values(preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * @return list<string> lowercased acronyms, e.g. ["it", "sql"]
     */
    private static function extractAcronyms(string $text): array
    {
        preg_match_all('/\b[A-Z]{2,6}\b/', strip_tags($text), $matches);

        return array_map('strtolower', $matches[0] ?? []);
    }

    /**
     * @param  list<string>  $words
     * @return array<string, int>
     */
    private static function withCounts(array $words): array
    {
        $counts = [];

        foreach ($words as $word) {
            $counts[$word] = ($counts[$word] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param  list<string>  $words
     * @param  callable(string): bool  $meaningful
     * @return array<string, int>
     */
    private static function bigrams(array $words, callable $meaningful): array
    {
        $counts = [];

        for ($i = 0; $i < count($words) - 1; $i++) {
            if (! $meaningful($words[$i]) || ! $meaningful($words[$i + 1])) {
                continue;
            }

            $phrase = $words[$i].' '.$words[$i + 1];
            $counts[$phrase] = ($counts[$phrase] ?? 0) + 1;
        }

        return $counts;
    }
}
