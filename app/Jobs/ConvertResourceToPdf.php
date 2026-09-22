<?php

namespace App\Jobs;

use App\Models\Resource;
use App\Support\Conversion\AppliesConversionResult;
use App\Support\Conversion\ConversionManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConvertResourceToPdf implements ShouldQueue
{
    use AppliesConversionResult, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        $this->applyResult($resource, $manager->convert($resource));
    }

    public function failed(\Throwable $e): void
    {
        $resource = Resource::find($this->resourceId);

        if ($resource) {
            $this->markFailed($resource, $e->getMessage());
        }
    }
}
