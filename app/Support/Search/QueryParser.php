<?php

namespace App\Support\Search;

/**
 * Understands what people type into a search box, the way a web search
 * engine does: plain words, "exact phrases", -excluded words, filler words
 * that can be ignored, and abbreviations that mean the same as longer
 * phrases ("os" ~ "operating system").
 */
class QueryParser
{
    private const MAX_SLOTS = 8;

    public function parse(string $raw): ParsedQuery
    {
        $raw = trim(mb_substr($raw, 0, 200));

        $phrases = [];
        $rest = preg_replace_callback('/"([^"]+)"/u', function (array $match) use (&$phrases) {
            $words = Tokenizer::words($match[1]);

            if ($words !== []) {
                $phrases[] = $words;
            }

            return ' ';
        }, $raw) ?? '';
        $rest = str_replace('"', ' ', $rest);

        $excluded = [];
        $rest = preg_replace_callback('/(?:^|\s)-([\p{L}\p{N}][\p{L}\p{N}+#]*)/u', function (array $match) use (&$excluded) {
            $words = Tokenizer::words($match[1]);

            if ($words !== []) {
                $excluded[] = $words[0];
            }

            return ' ';
        }, $rest) ?? '';

        $words = Tokenizer::words($rest);
        $core = array_values(array_filter($words, fn (string $word) => ! Tokenizer::isStopword($word)));

        // A query made only of filler ("the", "and") still means something.
        if ($core === [] && $phrases === []) {
            $core = $words;
        }

        $slots = $this->slotsFor($core);

        foreach ($phrases as $phraseWords) {
            $meaningful = array_values(array_filter($phraseWords, fn (string $word) => ! Tokenizer::isStopword($word)));
            $meaningful = $meaningful ?: $phraseWords;

            $slots[] = [
                'label' => implode(' ', $meaningful),
                'alts' => [['words' => $meaningful, 'factor' => 1.0, 'kind' => 'original']],
            ];
        }

        return new ParsedQuery(
            $raw,
            $this->unique($slots),
            $phrases,
            array_values(array_unique($excluded)),
        );
    }

    /**
     * @param  list<string>  $words
     * @return list<array{label: string, alts: list<array{words: list<string>, factor: float, kind: string}>}>
     */
    private function slotsFor(array $words): array
    {
        $groups = $this->synonymGroups();
        $slots = [];
        $count = count($words);
        $i = 0;

        while ($i < $count) {
            $consumed = 1;
            $sequence = [$words[$i]];
            $matchedGroups = $this->groupsContaining($groups, $sequence);

            // Prefer the longest multi-word synonym ("information
            // technology") over treating its words separately.
            for ($length = min(4, $count - $i); $length >= 2; $length--) {
                $candidate = array_slice($words, $i, $length);
                $found = $this->groupsContaining($groups, $candidate);

                if ($found !== []) {
                    $sequence = $candidate;
                    $matchedGroups = $found;
                    $consumed = $length;
                    break;
                }
            }

            $alts = [['words' => $sequence, 'factor' => 1.0, 'kind' => 'original']];

            foreach ($matchedGroups as $groupIndex) {
                foreach ($groups[$groupIndex] as $member) {
                    if ($member !== $sequence) {
                        $alts[] = ['words' => $member, 'factor' => 0.65, 'kind' => 'synonym'];
                    }
                }
            }

            $slots[] = ['label' => implode(' ', $sequence), 'alts' => $alts];
            $i += $consumed;
        }

        return $slots;
    }

    /**
     * @return list<list<list<string>>> groups of members, each member a word list
     */
    private function synonymGroups(): array
    {
        $groups = [];

        foreach (config('search.synonyms', []) as $group) {
            $members = [];

            foreach ($group as $member) {
                $words = Tokenizer::words($member);

                if ($words !== []) {
                    $members[] = $words;
                }
            }

            if (count($members) > 1) {
                $groups[] = $members;
            }
        }

        return $groups;
    }

    /**
     * @param  list<list<list<string>>>  $groups
     * @param  list<string>  $sequence
     * @return list<int>
     */
    private function groupsContaining(array $groups, array $sequence): array
    {
        $found = [];

        foreach ($groups as $index => $members) {
            foreach ($members as $member) {
                if ($member === $sequence) {
                    $found[] = $index;
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * @param  list<array{label: string, alts: list<array{words: list<string>, factor: float, kind: string}>}>  $slots
     * @return list<array{label: string, alts: list<array{words: list<string>, factor: float, kind: string}>}>
     */
    private function unique(array $slots): array
    {
        $seen = [];
        $result = [];

        foreach ($slots as $slot) {
            if (isset($seen[$slot['label']])) {
                continue;
            }

            $seen[$slot['label']] = true;
            $result[] = $slot;
        }

        return array_slice($result, 0, self::MAX_SLOTS);
    }
}
