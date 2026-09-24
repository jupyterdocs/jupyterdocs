<?php

namespace App\Support\Search;

/**
 * A search box entry taken apart. Each "slot" is one thing the searcher is
 * looking for (a word, or a phrase like "operating system"), together with
 * the alternative ways that thing might be written in a document.
 *
 * @phpstan-type Alternative array{words: list<string>, factor: float, kind: string}
 * @phpstan-type Slot array{label: string, alts: list<Alternative>}
 */
final class ParsedQuery
{
    /**
     * @param  list<array{label: string, alts: list<array{words: list<string>, factor: float, kind: string}>}>  $slots
     * @param  list<list<string>>  $phrases  "quoted phrases", as their words in order
     * @param  list<string>  $excluded  words after a minus sign
     */
    public function __construct(
        public readonly string $raw,
        public readonly array $slots,
        public readonly array $phrases,
        public readonly array $excluded,
    ) {}

    public function hasTerms(): bool
    {
        return $this->slots !== [];
    }
}
