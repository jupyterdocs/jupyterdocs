<?php

namespace App\Console\Commands;

use App\Jobs\LinearizeResourcePdf;
use App\Models\Resource;
use Illuminate\Console\Command;

class LinearizeResourcePdfs extends Command
{
    protected $signature = 'pdf:linearize-existing';

    protected $description = 'Queue linearization (fast web view) for every already-uploaded PDF';

    public function handle(): int
    {
        $count = 0;

        Resource::where('format', 'pdf')->whereNotNull('file_path')->select('id')
            ->chunkById(200, function ($resources) use (&$count) {
                foreach ($resources as $resource) {
                    LinearizeResourcePdf::dispatch($resource->id);
                    $count++;
                }
            });

        $this->info("Queued {$count} PDFs. Already-linearized files are skipped by the job.");

        return self::SUCCESS;
    }
}
