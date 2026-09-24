<?php

namespace App\Console\Commands;

use App\Support\Search\ResourceSearchIndexer;
use Illuminate\Console\Command;

class ReindexResourceSearch extends Command
{
    protected $signature = 'search:reindex';

    protected $description = 'Rebuild the document search index (run after renaming a course/university/tag, or if search results look stale)';

    public function handle(ResourceSearchIndexer $indexer): int
    {
        $count = $indexer->reindexAll(fn (int $done) => $this->line("Indexed {$done} documents…"));

        $this->components->info("Search index rebuilt for {$count} documents.");

        return self::SUCCESS;
    }
}
