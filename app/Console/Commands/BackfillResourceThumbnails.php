<?php

namespace App\Console\Commands;

use App\Models\Resource;
use App\Support\OfficeDocumentInspector;
use App\Support\ThumbnailStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillResourceThumbnails extends Command
{
    protected $signature = 'resources:backfill-thumbnails';

    protected $description = 'Extract embedded thumbnails and page counts for existing PowerPoint/Word/Excel uploads that are missing them';

    public function handle(): int
    {
        $resources = Resource::whereNull('thumbnail_path')
            ->whereIn('format', ['pptx', 'docx', 'xlsx'])
            ->get();

        $updated = 0;
        $disk = Storage::disk(config('filesystems.resource_disk'));

        foreach ($resources as $resource) {
            if (! $disk->exists($resource->file_path)) {
                $this->warn("Skipping #{$resource->id} — file missing on disk.");
                continue;
            }

            // ZipArchive needs a real local filesystem path; on a remote
            // disk (e.g. R2) that means downloading to a temp file first.
            $tempPath = tempnam(sys_get_temp_dir(), 'jd-backfill-');
            file_put_contents($tempPath, $disk->get($resource->file_path));

            try {
                $changes = [];

                if ($binary = OfficeDocumentInspector::extractThumbnail($tempPath)) {
                    if ($thumbnailPath = ThumbnailStorage::storeFromBinary($binary)) {
                        $changes['thumbnail_path'] = $thumbnailPath;
                    }
                }

                if (! $resource->pages) {
                    if ($pages = OfficeDocumentInspector::extractPageCount($tempPath, $resource->format)) {
                        $changes['pages'] = $pages;
                    }
                }

                if ($changes !== []) {
                    $resource->update($changes);
                    $updated++;
                    $this->line("#{$resource->id} {$resource->title} — updated (".implode(', ', array_keys($changes)).')');
                }
            } finally {
                @unlink($tempPath);
            }
        }

        $this->info("Done: updated {$updated} of {$resources->count()} resource(s) missing a thumbnail.");

        return self::SUCCESS;
    }
}
