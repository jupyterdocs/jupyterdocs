<?php

namespace App\Support\Search;

use App\Models\Resource;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Ranked document search. Roughly what a web search engine does, at the
 * scale of one library:
 *
 *  1. Look every query word (plus its plural/stem forms and synonyms) up in
 *     the inverted index — no scanning of titles and descriptions.
 *  2. Score each document by where each word matched (title >> tags >>
 *     description), how rare the word is, and how many of the searched
 *     words the document covers.
 *  3. Re-rank the best candidates on things only the full text shows:
 *     the whole query appearing as a phrase in the title, popularity,
 *     freshness, and "quoted phrase" checks.
 *  4. Words that match nothing are spell-corrected against the words that
 *     really occur in the library.
 */
class ResourceSearch
{
    public function __construct(private readonly QueryParser $parser) {}

    public function search(string $raw, ?int $typeId = null, bool $correct = true): SearchResult
    {
        $parsed = $this->parser->parse($raw);

        if (! $parsed->hasTerms()) {
            return SearchResult::none($raw);
        }

        $slots = $parsed->slots;
        $scored = $this->score($slots, $this->retrieve($slots, $typeId), $typeId);

        $corrections = [];

        if ($correct) {
            foreach ($scored['unmatched'] as $index) {
                $label = $slots[$index]['label'];

                if (! str_contains($label, ' ') && ($suggestion = $this->suggestSpelling($label))) {
                    $slots[$index]['alts'][] = ['words' => [$suggestion], 'factor' => 0.8, 'kind' => 'corrected'];
                    $corrections[$label] = $suggestion;
                }
            }

            if ($corrections !== []) {
                $scored = $this->score($slots, $this->retrieve($slots, $typeId), $typeId);
            }
        }

        $scores = $scored['scores'];

        if ($parsed->excluded !== []) {
            $scores = array_diff_key($scores, array_flip($this->documentsContaining($parsed->excluded, $typeId)));
        }

        $ids = $this->rank($parsed, $slots, $scores);

        [$exact, $roots] = $this->highlightTerms($slots);

        return new SearchResult(
            $raw,
            $ids,
            $exact,
            $roots,
            $corrections,
            $corrections !== [] ? $this->applyCorrections($raw, $corrections) : null,
        );
    }

    /**
     * Index rows for every document containing any word a slot could match.
     *
     * @param  list<array>  $slots
     * @return list<object>
     */
    private function retrieve(array $slots, ?int $typeId): array
    {
        $exact = [];
        $roots = [];

        foreach ($slots as $slot) {
            foreach ($slot['alts'] as $alt) {
                foreach ($alt['words'] as $word) {
                    foreach (Tokenizer::variants($word) as $form) {
                        $exact[$form] = true;
                    }

                    if ($root = Tokenizer::prefixRoot($word)) {
                        $roots[$root] = true;
                    }
                }
            }
        }

        return $this->approvedTerms($typeId)
            ->where(function (Builder $query) use ($exact, $roots) {
                // Numeric words ("2023") become int array keys; Postgres won't
                // compare those to a varchar column, so bind them as strings.
                $query->whereIn('t.term', array_map('strval', array_keys($exact)));

                foreach (array_keys($roots) as $root) {
                    $query->orWhere('t.term', 'like', $root.'%');
                }
            })
            ->limit(config('search.max_index_rows'))
            ->get(['t.resource_id', 't.term', 't.field', 't.hits'])
            ->all();
    }

    /**
     * @param  list<array>  $slots
     * @param  list<object>  $rows
     * @return array{scores: array<int, float>, unmatched: list<int>}
     */
    private function score(array $slots, array $rows, ?int $typeId): array
    {
        $fieldWeights = [
            ResourceSearchIndexer::TITLE => config('search.weights.title'),
            ResourceSearchIndexer::TAG => config('search.weights.tag'),
            ResourceSearchIndexer::COURSE => config('search.weights.course'),
            ResourceSearchIndexer::UNIVERSITY => config('search.weights.university'),
            ResourceSearchIndexer::TYPE => config('search.weights.type'),
            ResourceSearchIndexer::DESCRIPTION => config('search.weights.description'),
            ResourceSearchIndexer::FORMAT => config('search.weights.format'),
        ];

        // term => where it can match, so each index row is one lookup.
        $exactLookup = [];
        $rootLookup = [];

        foreach ($slots as $s => $slot) {
            foreach ($slot['alts'] as $a => $alt) {
                foreach ($alt['words'] as $w => $word) {
                    $exactLookup[$word][] = [$s, $a, $w, 1.0];

                    foreach (Tokenizer::variants($word) as $form) {
                        if ($form !== $word) {
                            $exactLookup[$form][] = [$s, $a, $w, 0.95];
                        }
                    }

                    if ($root = Tokenizer::prefixRoot($word)) {
                        $rootLookup[] = [$root, $s, $a, $w];
                    }
                }
            }
        }

        // best[document][slot][alternative][word] = [value, field]
        $best = [];

        foreach ($rows as $row) {
            $document = (int) $row->resource_id;
            $field = (int) $row->field;
            $term = (string) $row->term;

            $tf = $field === ResourceSearchIndexer::DESCRIPTION ? 1 + 0.15 * log(max(1, (int) $row->hits)) : 1;
            $base = $fieldWeights[$field] * $tf;

            $hits = $exactLookup[$term] ?? [];

            foreach ($rootLookup as [$root, $s, $a, $w]) {
                if (str_starts_with($term, $root)) {
                    $hits[] = [$s, $a, $w, 0.7];
                }
            }

            foreach ($hits as [$s, $a, $w, $quality]) {
                $value = $base * $quality;

                if ($value > ($best[$document][$s][$a][$w][0] ?? 0)) {
                    $best[$document][$s][$a][$w] = [$value, $field];
                }
            }
        }

        // slotScores[document][slot] = [value, field of the deciding match]
        $slotScores = [];
        $documentFrequency = array_fill_keys(array_keys($slots), 0);

        foreach ($best as $document => $bySlot) {
            foreach ($bySlot as $s => $byAlternative) {
                $top = 0.0;
                $topField = null;

                foreach ($byAlternative as $a => $byWord) {
                    $needed = count($slots[$s]['alts'][$a]['words']);

                    if (count($byWord) < $needed) {
                        continue; // every word of a multi-word alternative must be present
                    }

                    $weakest = min(array_column($byWord, 0));
                    $value = $weakest * $slots[$s]['alts'][$a]['factor'];

                    if ($value > $top) {
                        $top = $value;
                        $topField = collect($byWord)->sortBy(0)->first()[1];
                    }
                }

                if ($top > 0) {
                    $slotScores[$document][$s] = [$top, $topField];
                    $documentFrequency[$s]++;
                }
            }
        }

        $unmatched = array_keys(array_filter($documentFrequency, fn (int $df) => $df === 0));

        $total = max(1, Resource::approved()
            ->when($typeId, fn ($query) => $query->where('resource_type_id', $typeId))
            ->count());

        $idf = [];
        $common = [];

        foreach ($documentFrequency as $s => $df) {
            $idf[$s] = log(1 + ($total - $df + 0.5) / ($df + 0.5));
            // A word in a third of the library says little on its own.
            $common[$s] = $total >= 20 && $df / $total > 0.33;
        }

        $slotCount = count($slots);
        $matchedCounts = [];
        $sums = [];

        foreach ($slotScores as $document => $bySlot) {
            $matched = 0;
            $sum = 0.0;

            foreach ($bySlot as $s => [$value, $field]) {
                $sum += $value * $idf[$s];

                if (! ($common[$s] && $field === ResourceSearchIndexer::DESCRIPTION)) {
                    $matched++;
                }
            }

            $matchedCounts[$document] = $matched;
            $sums[$document] = $sum;
        }

        // Like a web search: prefer documents that cover most of what was
        // asked for, and only fall back to partial matches when nothing
        // covers enough.
        $threshold = $matchedCounts === []
            ? 0
            : min(max($matchedCounts), max(1, (int) ceil(0.6 * $slotCount)));

        $scores = [];

        foreach ($sums as $document => $sum) {
            if ($matchedCounts[$document] < $threshold) {
                continue;
            }

            $coverage = $matchedCounts[$document] / $slotCount;
            $scores[$document] = $sum * (0.35 + 0.65 * $coverage ** 2) * ($coverage >= 1 ? 1.2 : 1.0);
        }

        return ['scores' => $scores, 'unmatched' => $unmatched];
    }

    /**
     * Second pass over the strongest candidates using the actual text.
     *
     * @param  list<array>  $slots
     * @param  array<int, float>  $scores
     * @return list<int>
     */
    private function rank(ParsedQuery $parsed, array $slots, array $scores): array
    {
        if ($scores === []) {
            return [];
        }

        arsort($scores);
        $ordered = array_keys($scores);

        $window = $parsed->phrases !== [] ? 2000 : config('search.refine_top');
        $candidates = array_slice($ordered, 0, $window);
        $rest = $parsed->phrases !== [] ? [] : array_slice($ordered, $window);

        $queryWords = [];

        foreach ($slots as $slot) {
            foreach ($slot['alts'][0]['words'] as $word) {
                $queryWords[] = $word;
            }
        }

        $documents = collect();

        foreach (array_chunk($candidates, 500) as $chunk) {
            $documents = $documents->merge(
                Resource::whereIn('id', $chunk)->get(['id', 'title', 'description', 'downloads_count', 'created_at'])
            );
        }

        $documents = $documents->keyBy('id');
        $final = [];

        foreach ($candidates as $id) {
            $document = $documents->get($id);

            if (! $document) {
                continue;
            }

            $titleWords = Tokenizer::words($document->title);
            $descriptionWords = Tokenizer::words((string) $document->description);

            if (! $this->containsAllPhrases($parsed->phrases, $titleWords, $descriptionWords)) {
                continue;
            }

            $final[$id] = $scores[$id] + $this->textBonus($parsed, $queryWords, $titleWords, $descriptionWords, $document);
        }

        // Ties go to the newer document.
        uksort($final, fn ($a, $b) => $final[$b] <=> $final[$a] ?: $b <=> $a);

        return array_merge(array_keys($final), $rest);
    }

    /**
     * @param  list<list<string>>  $phrases
     * @param  list<string>  $titleWords
     * @param  list<string>  $descriptionWords
     */
    private function containsAllPhrases(array $phrases, array $titleWords, array $descriptionWords): bool
    {
        foreach ($phrases as $phrase) {
            $needle = ' '.implode(' ', $phrase).' ';

            $inTitle = str_contains(' '.implode(' ', $titleWords).' ', $needle);
            $inDescription = str_contains(' '.implode(' ', $descriptionWords).' ', $needle);

            if (! $inTitle && ! $inDescription) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $queryWords
     * @param  list<string>  $titleWords
     * @param  list<string>  $descriptionWords
     */
    private function textBonus(ParsedQuery $parsed, array $queryWords, array $titleWords, array $descriptionWords, Resource $document): float
    {
        $bonus = 0.0;

        $title = ' '.implode(' ', array_filter($titleWords, fn ($w) => ! Tokenizer::isStopword($w))).' ';
        $description = ' '.implode(' ', array_filter($descriptionWords, fn ($w) => ! Tokenizer::isStopword($w))).' ';

        if (count($queryWords) > 1) {
            $whole = ' '.implode(' ', $queryWords).' ';

            if (str_contains($title, $whole)) {
                $bonus += 35;

                if (trim($title) === trim($whole)) {
                    $bonus += 25;
                }
            }

            for ($i = 0; $i < count($queryWords) - 1; $i++) {
                $pair = ' '.$queryWords[$i].' '.$queryWords[$i + 1].' ';

                $bonus += str_contains($title, $pair) ? 6 : 0;
                $bonus += str_contains($description, $pair) ? 2 : 0;
            }
        } elseif ($queryWords !== [] && trim($title) === $queryWords[0]) {
            $bonus += 30;
        }

        if ($queryWords !== [] && str_starts_with(ltrim($title), $queryWords[0].' ')) {
            $bonus += 8;
        }

        foreach ($parsed->phrases as $phrase) {
            $needle = ' '.implode(' ', $phrase).' ';
            $bonus += str_contains(' '.implode(' ', $titleWords).' ', $needle) ? 20 : 10;
        }

        $bonus += 1.2 * log(1 + (int) $document->downloads_count);

        $ageDays = $document->created_at ? max(0, (time() - $document->created_at->getTimestamp()) / 86400) : 365;
        $bonus += 3 * exp(-$ageDays / 365);

        return $bonus;
    }

    /**
     * @param  list<string>  $words
     * @return list<int>
     */
    private function documentsContaining(array $words, ?int $typeId): array
    {
        $forms = [];

        foreach ($words as $word) {
            foreach (Tokenizer::variants($word) as $form) {
                $forms[$form] = true;
            }
        }

        return $this->approvedTerms($typeId)
            ->whereIn('t.term', array_map('strval', array_keys($forms)))
            ->distinct()
            ->pluck('t.resource_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * The closest word that genuinely occurs in the library's titles, tags
     * and courses — but only for a word that occurs nowhere at all, so real
     * (if rare) words are never "corrected".
     */
    private function suggestSpelling(string $word): ?string
    {
        $length = strlen($word);

        if ($length < 4 || ! ctype_alpha($word) || $this->wordExists($word)) {
            return null;
        }

        $tolerance = $length <= 5 ? 1 : ($length >= 10 ? 3 : 2);

        // Most typos keep the first letter, so look there first.
        foreach ([true, false] as $sameInitial) {
            $query = $this->approvedTerms(null)
                ->whereIn('t.field', ResourceSearchIndexer::VOCABULARY_FIELDS)
                ->whereRaw('LENGTH(t.term) BETWEEN ? AND ?', [$length - $tolerance, $length + $tolerance]);

            if ($sameInitial) {
                $query->where('t.term', 'like', $word[0].'%');
            }

            $best = null;
            $bestDistance = $tolerance + 1;
            $bestFrequency = 0;

            $candidates = $query->groupBy('t.term')
                ->selectRaw('t.term as term, COUNT(DISTINCT t.resource_id) as df')
                ->get();

            foreach ($candidates as $candidate) {
                if ($candidate->term === $word || ! ctype_alpha($candidate->term)) {
                    continue;
                }

                $distance = Tokenizer::distance($word, $candidate->term, $tolerance);

                if ($distance > $tolerance) {
                    continue;
                }

                if ($distance < $bestDistance || ($distance === $bestDistance && $candidate->df > $bestFrequency)) {
                    $best = $candidate->term;
                    $bestDistance = $distance;
                    $bestFrequency = $candidate->df;
                }
            }

            if ($best !== null) {
                return $best;
            }
        }

        return null;
    }

    private function wordExists(string $word): bool
    {
        return $this->approvedTerms(null)
            ->where(function (Builder $query) use ($word) {
                $query->whereIn('t.term', Tokenizer::variants($word));

                if ($root = Tokenizer::prefixRoot($word)) {
                    $query->orWhere('t.term', 'like', $root.'%');
                }
            })
            ->exists();
    }

    private function approvedTerms(?int $typeId): Builder
    {
        return DB::table('resource_search_terms as t')
            ->join('resources as r', 'r.id', '=', 't.resource_id')
            ->where('r.status', 'approved')
            ->whereNull('r.deleted_at')
            ->when($typeId, fn (Builder $query) => $query->where('r.resource_type_id', $typeId));
    }

    /**
     * @param  array<string, string>  $corrections
     */
    private function applyCorrections(string $raw, array $corrections): string
    {
        foreach ($corrections as $typo => $suggestion) {
            $raw = preg_replace('/\b'.preg_quote($typo, '/').'\b/iu', $suggestion, $raw) ?? $raw;
        }

        return $raw;
    }

    /**
     * @param  list<array>  $slots
     * @return array{0: list<string>, 1: list<string>}
     */
    private function highlightTerms(array $slots): array
    {
        $exact = [];
        $roots = [];

        foreach ($slots as $slot) {
            foreach ($slot['alts'] as $alt) {
                foreach ($alt['words'] as $word) {
                    if (strlen($word) < 3) {
                        continue;
                    }

                    foreach (Tokenizer::variants($word) as $form) {
                        $exact[$form] = true;
                    }

                    if ($root = Tokenizer::prefixRoot($word)) {
                        $roots[$root] = true;
                    }
                }
            }
        }

        return [array_map('strval', array_keys($exact)), array_map('strval', array_keys($roots))];
    }
}
