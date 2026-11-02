<?php

namespace App\Jobs;

use App\Models\Resource;
use App\Support\Pdf\PdfLinearizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Replaces an uploaded (native) PDF with its linearized copy so previews
 * load fast. Idempotent: already-linearized files are left alone, and any
 * failure leaves the original upload untouched.
 */
class LinearizeResourcePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(private readonly int $resourceId) {}

    public function handle(PdfLinearizer $linearizer): void
    {
        $resource = Resource::find($this->resourceId);

        if (! $resource || $resource->format !== 'pdf' || ! $resource->file_path || ! $linearizer->available()) {
            return;
        }

        $disk = Storage::disk(config('filesystems.resource_disk'));
        $source = $disk->readStream($resource->file_path);

        if (! $source) {
            return;
        }

        $in = tempnam(sys_get_temp_dir(), 'pdfin');
        $out = tempnam(sys_get_temp_dir(), 'pdfout');

        try {
            $target = fopen($in, 'wb');
            stream_copy_to_stream($source, $target);
            fclose($target);
            fclose($source);

            if ($linearizer->isLinearized($in) || ! $linearizer->linearize($in, $out)) {
                return;
            }

            $stream = fopen($out, 'rb');
            $disk->writeStream($resource->file_path, $stream, ['ContentType' => 'application/pdf']);
            fclose($stream);

            $resource->file_size = filesize($out);
            $resource->save();
        } finally {
            @unlink($in);
            @unlink($out);
        }
    }
}
