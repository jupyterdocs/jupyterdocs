<?php

namespace App\Support\Conversion\Drivers;

use App\Models\Resource;
use App\Support\Conversion\ConversionDriver;
use App\Support\Conversion\ConversionResult;
use App\Support\Conversion\QuotaTracker;
use CloudConvert\CloudConvert;
use CloudConvert\Models\Job;
use CloudConvert\Models\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CloudConvertDriver implements ConversionDriver
{
    public function __construct(private readonly QuotaTracker $quota) {}

    public function convert(Resource $resource): ConversionResult
    {
        if (! config('services.cloudconvert.api_key')) {
            return ConversionResult::failure('CloudConvert API key is not configured.');
        }

        if (! $this->quota->hasQuota()) {
            return ConversionResult::failure('CloudConvert daily quota exhausted.');
        }

        $client = new CloudConvert([
            'api_key' => config('services.cloudconvert.api_key'),
            'sandbox' => (bool) config('services.cloudconvert.sandbox'),
        ]);

        try {
            $job = (new Job)
                ->addTask(new Task('import/upload', 'import-file'))
                ->addTask(
                    (new Task('convert', 'convert-file'))
                        ->set('input', ['import-file'])
                        ->set('input_format', $resource->format)
                        ->set('output_format', 'pdf')
                )
                ->addTask(
                    (new Task('export/url', 'export-file'))
                        ->set('input', ['convert-file'])
                );

            $job = $client->jobs()->create($job);

            /** @var Task $uploadTask */
            $uploadTask = $job->getTasks()->whereName('import-file')[0];

            $sourceDisk = Storage::disk(config('filesystems.resource_disk'));
            $stream = $sourceDisk->readStream($resource->file_path);

            $client->tasks()->upload(
                $uploadTask,
                $stream,
                $resource->title.'.'.$resource->format
            );

            // Quota is consumed by the job actually running, so record it
            // right after the upload succeeds rather than speculatively.
            $this->quota->recordUsage();

            $client->jobs()->wait($job);
            $job = $client->jobs()->get($job->getId());

            /** @var Task $exportTask */
            $exportTask = $job->getTasks()->whereName('export-file')[0];

            if ($exportTask->getStatus() !== 'finished') {
                return ConversionResult::failure(
                    'CloudConvert export task did not finish: '.($exportTask->getMessage() ?? 'unknown error')
                );
            }

            $file = $exportTask->getResult()->files[0];
            $response = Http::timeout(60)->get($file->url);

            if (! $response->successful()) {
                return ConversionResult::failure('Could not download converted PDF from CloudConvert.');
            }

            return ConversionResult::success('cloudconvert', $response->body());
        } catch (Throwable $e) {
            Log::warning('CloudConvert conversion failed', [
                'resource_id' => $resource->id,
                'error' => $e->getMessage(),
            ]);

            // If CloudConvert itself is the one saying "quota exceeded",
            // trust it over our own counter for the rest of the day.
            if (str_contains(strtolower($e->getMessage()), 'quota')) {
                $this->quota->exhaustQuotaForToday();
            }

            return ConversionResult::failure($e->getMessage());
        }
    }
}
