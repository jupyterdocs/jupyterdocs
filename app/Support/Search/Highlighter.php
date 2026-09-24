<?php

namespace App\Support\Search;

use Illuminate\Support\HtmlString;

/**
 * Bolds the searched words inside a title or description, and cuts a
 * snippet of description around the first match — the two things that make
 * a results page look like a search engine's. Text is escaped piece by
 * piece, so highlighting can never open an HTML injection hole.
 */
class Highlighter
{
    private ?string $pattern = null;

    /**
     * @param  list<string>  $exact  whole words to highlight
     * @param  list<string>  $roots  stems to highlight along with whatever follows them
     */
    public function __construct(array $exact, array $roots)
    {
        $roots = array_map('strval', $roots);
        $exact = array_map('strval', $exact);

        usort($roots, fn ($a, $b) => strlen($b) <=> strlen($a));
        usort($exact, fn ($a, $b) => strlen($b) <=> strlen($a));

        $alternatives = [];

        foreach ($roots as $root) {
            $alternatives[] = preg_quote($root, '/').'[\p{L}\p{N}]*';
        }

        foreach ($exact as $word) {
            $alternatives[] = preg_quote($word, '/');
        }

        if ($alternatives !== []) {
            $this->pattern = '/(?<![\p{L}\p{N}])(?:'.implode('|', $alternatives).')(?![\p{L}\p{N}])/iu';
        }
    }

    public function highlight(?string $text): HtmlString
    {
        return new HtmlString($this->mark((string) $text));
    }

    /**
     * A short excerpt centred on the first match, with the match marked.
     */
    public function snippet(?string $text, int $length = 160): HtmlString
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', (string) $text));

        if ($text === '') {
            return new HtmlString('');
        }

        if (mb_strlen($text) <= $length) {
            return new HtmlString($this->mark($text));
        }

        $start = 0;

        if ($this->pattern && preg_match($this->pattern, $text, $match, PREG_OFFSET_CAPTURE)) {
            $position = mb_strlen(substr($text, 0, $match[0][1]));
            $start = max(0, $position - 40);

            // Begin on a word boundary rather than mid-word.
            if ($start > 0) {
                $space = mb_strpos($text, ' ', $start);

                if ($space !== false && $space < $position) {
                    $start = $space + 1;
                }
            }
        }

        $excerpt = mb_substr($text, $start, $length);
        $prefix = $start > 0 ? '…' : '';
        $suffix = $start + $length < mb_strlen($text) ? '…' : '';

        return new HtmlString($prefix.$this->mark($excerpt).$suffix);
    }

    private function mark(string $text): string
    {
        if ($this->pattern === null || $text === '') {
            return e($text);
        }

        preg_match_all($this->pattern, $text, $matches, PREG_OFFSET_CAPTURE);

        $output = '';
        $cursor = 0;

        foreach ($matches[0] as [$word, $offset]) {
            $output .= e(substr($text, $cursor, $offset - $cursor));
            $output .= '<mark class="jd-mark">'.e($word).'</mark>';
            $cursor = $offset + strlen($word);
        }

        return $output.e(substr($text, $cursor));
    }
}
