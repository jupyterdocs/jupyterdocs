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

        foreach ($resources as $resource) {
            if (! Storage::disk('local')->exists($resource->file_path)) {
                $this->warn("Skipping #{$resource->id} — file missing on disk.");
                continue;
            }

            $absolutePath = Storage::disk('local')->path($resource->file_path);
            $changes = [];

            if ($binary = OfficeDocumentInspector::extractThumbnail($absolutePath)) {
                if ($thumbnailPath = ThumbnailStorage::storeFromBinary($binary)) {
                    $changes['thumbnail_path'] = $thumbnailPath;
                }
            }

            if (! $resource->pages) {
                if ($pages = OfficeDocumentInspector::extractPageCount($absolutePath, $resource->format)) {
                    $changes['pages'] = $pages;
                }
            }

            if ($changes !== []) {
                $resource->update($changes);
                $updated++;
                $this->line("#{$resource->id} {$resource->title} — updated (".implode(', ', array_keys($changes)).')');
            }
        }

        $this->info("Done: updated {$updated} of {$resources->count()} resource(s) missing a thumbnail.");

        return self::SUCCESS;
    }
}
