<?php

namespace App\Jobs;

use App\Models\Resource;
use App\Support\Conversion\AppliesConversionResult;
use App\Support\Conversion\Drivers\LocalCliDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * The edge-computing path: only ever dispatched onto the
 * `conversion.local.queue` queue by an admin who clicked "convert on this
 * device" in the dashboard, and only ever picked up by a worker an admin
 * started themselves via `php artisan conversion:work-local` on their own
 * machine. It never touches CloudConvert or Gotenberg — if this machine
 * doesn't have LibreOffice, it fails fast instead of falling back, so the
 * resource stays eligible for the normal remote pipeline to retry.
 */
class ConvertResourceToPdfLocally implements ShouldQueue
{
    use AppliesConversionResult, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // A machine that goes offline mid-job shouldn't retry into a queue
    // nobody is listening to anymore; a stuck resource just falls back to
    // the normal remote pipeline's next attempt.
    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(private readonly int $resourceId) {}

    public function handle(LocalCliDriver $driver): void
    {
        $resource = Resource::find($this->resourceId);

        if (! $resource) {
            return;
        }

        $resource->conversion_status = 'processing';
        $resource->save();

        $this->applyResult($resource, $driver->convert($resource));
    }

    public function failed(\Throwable $e): void
    {
        $resource = Resource::find($this->resourceId);

        if ($resource) {
            $this->markFailed($resource, $e->getMessage());
        }
    }
}
