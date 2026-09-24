<?php

namespace App\Support\Search;

use Illuminate\Support\Str;

/**
 * Turns free text into comparable search words. The same rules run when a
 * document is indexed and when a query is parsed, which is what makes
 * "Programming" match a search for "programs" and "CAFÉ" match "cafe".
 */
class Tokenizer
{
    /** Longest-first; the first one that still leaves a real stem wins. */
    private const SUFFIXES = [
        'ations', 'ation', 'ically', 'ical', 'ings', 'ing', 'ments', 'ment',
        'ities', 'ity', 'ives', 'ive', 'ness', 'ions', 'ion', 'ers', 'er',
        'ors', 'or', 'ics', 'ic', 'als', 'al', 'ed', 'es', 's',
    ];

    private static ?array $synonymWords = null;

    private static ?array $stopwords = null;

    public static function reset(): void
    {
        self::$synonymWords = null;
        self::$stopwords = null;
    }

    public static function normalize(string $text): string
    {
        $text = mb_strtolower(Str::ascii($text));

        return str_replace(["'", '’', '`'], '', $text);
    }

    /**
     * Every word in order, stopwords included (callers decide what to do
     * with them). Keeps c++ / c# style tokens intact.
     *
     * @return list<string>
     */
    public static function words(string $text): array
    {
        preg_match_all('/[a-z0-9]+(?:[+#]+)?/', self::normalize($text), $matches);

        return array_values(array_filter(
            $matches[0],
            fn (string $word) => strlen($word) <= 40
        ));
    }

    public static function isStopword(string $word): bool
    {
        self::$stopwords ??= array_flip(config('search.stopwords', []));

        return isset(self::$stopwords[$word]) && ! self::isSynonymWord($word);
    }

    /**
     * A stopword like "it" is still worth keeping when it is also an
     * acronym people search for ("IT professionals").
     */
    public static function isSynonymWord(string $word): bool
    {
        if (self::$synonymWords === null) {
            self::$synonymWords = [];

            foreach (config('search.synonyms', []) as $group) {
                foreach ($group as $member) {
                    $parts = self::words($member);

                    if (count($parts) === 1) {
                        self::$synonymWords[$parts[0]] = true;
                    }
                }
            }
        }

        return isset(self::$synonymWords[$word]);
    }

    /**
     * Term => how many times it appears, with stopwords dropped.
     *
     * @return array<string, int>
     */
    public static function indexTerms(?string $text): array
    {
        $terms = [];

        foreach (self::words((string) $text) as $word) {
            if (self::isStopword($word)) {
                continue;
            }

            $terms[$word] = ($terms[$word] ?? 0) + 1;
        }

        return $terms;
    }

    /**
     * The word plus its plain singular/plural forms — matches that are
     * effectively exact ("system" / "systems", "study" / "studies").
     *
     * @return list<string>
     */
    public static function variants(string $word): array
    {
        $forms = [$word];
        $length = strlen($word);

        if ($length < 3 || ! ctype_alpha($word)) {
            return $forms;
        }

        if (str_ends_with($word, 'ies') && $length > 4) {
            $forms[] = substr($word, 0, -3).'y';
        } elseif (str_ends_with($word, 'sses')) {
            $forms[] = substr($word, 0, -2);
        } elseif (str_ends_with($word, 'es') && $length > 4) {
            $forms[] = substr($word, 0, -2);
            $forms[] = substr($word, 0, -1);
        } elseif (str_ends_with($word, 's') && ! preg_match('/(ss|us|is)$/', $word)) {
            $forms[] = substr($word, 0, -1);
        }

        if (! str_ends_with($word, 's')) {
            if (preg_match('/[^aeiou]y$/', $word)) {
                $forms[] = substr($word, 0, -1).'ies';
            } elseif (preg_match('/(x|z|ch|sh)$/', $word)) {
                $forms[] = $word.'es';
            } else {
                $forms[] = $word.'s';
            }
        }

        return array_values(array_unique($forms));
    }

    /**
     * A stem to prefix-match on, so "programming", "programs" and
     * "programmer" all find each other. Null for short or non-alphabetic
     * words, which only ever match exactly.
     */
    public static function prefixRoot(string $word): ?string
    {
        if (! ctype_alpha($word) || strlen($word) < 5) {
            return null;
        }

        $root = $word;

        foreach (self::SUFFIXES as $suffix) {
            if (! str_ends_with($word, $suffix)) {
                continue;
            }

            // A bare plural ("physics" -> "physic") leaves a stem short
            // enough to swallow unrelated words ("physician"), so it must
            // keep more letters than a real derivational suffix does.
            $minimum = in_array($suffix, ['es', 's'], true) ? 7 : 5;

            if (strlen($word) - strlen($suffix) >= $minimum) {
                $root = substr($word, 0, -strlen($suffix));
                break;
            }
        }

        // programm(ing) -> program, but keep real double letters like "ll".
        if (preg_match('/([bdgmnprt])\1$/', $root)) {
            $root = substr($root, 0, -1);
        }

        // comput(e) / comput(er) / comput(ing) all share one root.
        if ($root === $word && str_ends_with($root, 'e') && strlen($root) >= 6) {
            $root = substr($root, 0, -1);
        }

        return strlen($root) >= 5 ? $root : null;
    }

    /**
     * Edit distance counting a swapped pair of letters as one typo, with an
     * early exit once it is clearly further than $max.
     */
    public static function distance(string $a, string $b, int $max = 3): int
    {
        $lengthA = strlen($a);
        $lengthB = strlen($b);

        if (abs($lengthA - $lengthB) > $max) {
            return $max + 1;
        }

        $previousPrevious = [];
        $previous = range(0, $lengthB);

        for ($i = 1; $i <= $lengthA; $i++) {
            $current = [$i];
            $rowMin = $i;

            for ($j = 1; $j <= $lengthB; $j++) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
                $value = min($previous[$j] + 1, $current[$j - 1] + 1, $previous[$j - 1] + $cost);

                if ($i > 1 && $j > 1 && $a[$i - 1] === $b[$j - 2] && $a[$i - 2] === $b[$j - 1]) {
                    $value = min($value, $previousPrevious[$j - 2] + 1);
                }

                $current[] = $value;
                $rowMin = min($rowMin, $value);
            }

            if ($rowMin > $max) {
                return $max + 1;
            }

            $previousPrevious = $previous;
            $previous = $current;
        }

        return $previous[$lengthB];
    }
}
