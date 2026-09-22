<?php

namespace App\Support\Conversion;

use App\Models\Resource;
use Illuminate\Support\Facades\Storage;

/**
 * Shared by every conversion job (remote and local) so the "save the PDF,
 * flip conversion_status, remember which driver did it" bookkeeping only
 * lives in one place.
 */
trait AppliesConversionResult
{
    protected function applyResult(Resource $resource, ConversionResult $result): void
    {
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

    protected function markFailed(Resource $resource, string $message): void
    {
        $resource->conversion_status = 'failed';
        $resource->conversion_error = $message;
        $resource->save();
    }
}
