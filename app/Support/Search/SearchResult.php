<?php

namespace App\Support\Search;

final class SearchResult
{
    /**
     * @param  list<int>  $ids  best match first
     * @param  list<string>  $highlightExact  words to highlight as whole words
     * @param  list<string>  $highlightRoots  stems to highlight as word prefixes
     * @param  array<string, string>  $corrections  misspelt word => the word searched instead
     */
    public function __construct(
        public readonly string $query,
        public readonly array $ids,
        public readonly array $highlightExact = [],
        public readonly array $highlightRoots = [],
        public readonly array $corrections = [],
        public readonly ?string $correctedQuery = null,
        public readonly bool $searched = true,
    ) {}

    public static function none(string $query): self
    {
        return new self($query, [], searched: false);
    }

    public function total(): int
    {
        return count($this->ids);
    }

    public function wasCorrected(): bool
    {
        return $this->correctedQuery !== null;
    }

    public function highlighter(): Highlighter
    {
        return new Highlighter($this->highlightExact, $this->highlightRoots);
    }
}
