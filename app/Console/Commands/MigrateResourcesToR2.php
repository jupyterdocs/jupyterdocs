<?php

namespace App\Console\Commands;

use App\Models\Resource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * One-time migration of already-uploaded files/thumbnails from local disk
 * to R2. Safe to re-run — skips anything already present on the R2 side.
 * Never deletes local originals unless --delete-source is explicitly passed.
 */
class MigrateResourcesToR2 extends Command
{
    protected $signature = 'resources:migrate-to-r2
        {--dry-run : Show what would be migrated without writing anything}
        {--delete-source : Delete the local copy after a verified successful copy}
        {--chunk=100 : How many resources to process per batch}';

    protected $description = 'Copy existing resource files and thumbnails from local disk to R2';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $deleteSource = (bool) $this->option('delete-source');
        $chunk = (int) $this->option('chunk');

        $local = Storage::disk('local');
        $public = Storage::disk('public');
        $r2 = Storage::disk('r2');
        $r2Public = Storage::disk('r2_public');

        $stats = ['migrated' => 0, 'already_present' => 0, 'missing_source' => 0, 'failed' => 0];

        $total = Resource::withTrashed()->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Resource::withTrashed()->chunkById($chunk, function ($resources) use (
            $local, $public, $r2, $r2Public, $dryRun, $deleteSource, &$stats, $bar
        ) {
            foreach ($resources as $resource) {
                $this->migrateFile($resource->file_path, $local, $r2, $dryRun, $deleteSource, $stats);
                $this->migrateFile($resource->thumbnail_path, $public, $r2Public, $dryRun, $deleteSource, $stats);
                $this->migrateFile($resource->converted_pdf_path, $local, $r2, $dryRun, $deleteSource, $stats);

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(['Migrated', 'Already present', 'Missing source', 'Failed'], [[
            $stats['migrated'], $stats['already_present'], $stats['missing_source'], $stats['failed'],
        ]]);

        if ($dryRun) {
            $this->comment('Dry run — nothing was written.');
        }

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function migrateFile(?string $path, $sourceDisk, $destDisk, bool $dryRun, bool $deleteSource, array &$stats): void
    {
        if (! $path) {
            return;
        }

        if ($destDisk->exists($path)) {
            $stats['already_present']++;

            return;
        }

        if (! $sourceDisk->exists($path)) {
            $stats['missing_source']++;
            $this->warn("Missing source file: {$path}");

            return;
        }

        if ($dryRun) {
            $stats['migrated']++;

            return;
        }

        try {
            $bytes = $sourceDisk->get($path);
            $destDisk->put($path, $bytes);

            if ($destDisk->size($path) !== $sourceDisk->size($path)) {
                throw new \RuntimeException('Size mismatch after copy.');
            }

            $stats['migrated']++;

            if ($deleteSource) {
                $sourceDisk->delete($path);
            }
        } catch (\Throwable $e) {
            $stats['failed']++;
            $this->error("Failed to migrate {$path}: {$e->getMessage()}");
        }
    }
}
