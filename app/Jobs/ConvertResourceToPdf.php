<?php

namespace App\Jobs;

use App\Models\Resource;
use App\Support\Conversion\ConversionManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ConvertResourceToPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public int $timeout = 180;

    public function __construct(private readonly int $resourceId) {}

    public function handle(ConversionManager $manager): void
    {
        $resource = Resource::find($this->resourceId);

        if (! $resource) {
            return;
        }

        $resource->conversion_status = 'processing';
        $resource->save();

        $result = $manager->convert($resource);

        if (! $result->success) {
            $resource->conversion_status = 'failed';
            $resource->conversion_error = $result->errorMessage;
            $resource->save();

            return;
        }

        $path = config('conversion.converted_path_prefix')."/{$resource->id}.pdf";

        Storage::disk(config('filesystems.resource_disk'))->put($path, $result->pdfContents);

        $resource->converted_pdf_path = $path;
        $resource->conversion_status = 'done';
        $resource->conversion_driver = $result->driver;
        $resource->converted_at = now();
        $resource->conversion_error = null;
        $resource->save();
    }

    public function failed(\Throwable $e): void
    {
        $resource = Resource::find($this->resourceId);

        if ($resource) {
            $resource->conversion_status = 'failed';
            $resource->conversion_error = $e->getMessage();
            $resource->save();
        }
    }
}
