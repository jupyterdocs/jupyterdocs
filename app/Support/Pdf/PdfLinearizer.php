<?php

namespace App\Support\Pdf;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Rewrites PDFs as "linearized" (fast web view) files with qpdf, so pdf.js
 * can show page 1 after reading the first few hundred KB instead of
 * crawling a big file chunk by chunk to find its page tree. Lossless, and
 * every method degrades to a no-op when qpdf isn't installed.
 */
class PdfLinearizer
{
    public function available(): bool
    {
        return Process::timeout(10)->run([$this->binary(), '--version'])->successful();
    }

    public function isLinearized(string $path): bool
    {
        return Process::timeout(30)->run([$this->binary(), '--check-linearization', $path])->successful();
    }

    /**
     * Linearizes the file at $inputPath into $outputPath. Returns false (and
     * leaves no output behind) on any failure. qpdf exits 3 when it finished
     * with warnings, which still produces a valid file.
     */
    public function linearize(string $inputPath, string $outputPath): bool
    {
        $result = Process::timeout((int) config('conversion.linearize.timeout'))
            ->run([$this->binary(), '--linearize', $inputPath, $outputPath]);

        $ok = in_array($result->exitCode(), [0, 3], true)
            && is_file($outputPath)
            && filesize($outputPath) > 0;

        if (! $ok) {
            Log::warning('PDF linearization failed', ['exit' => $result->exitCode(), 'error' => trim($result->errorOutput())]);
            @unlink($outputPath);
        }

        return $ok;
    }

    /**
     * In-memory convenience for freshly converted PDFs. Returns the original
     * bytes untouched if qpdf is missing or fails.
     */
    public function linearizeContents(string $contents): string
    {
        $in = tempnam(sys_get_temp_dir(), 'pdfin');
        $out = tempnam(sys_get_temp_dir(), 'pdfout');

        try {
            file_put_contents($in, $contents);

            return $this->linearize($in, $out) ? file_get_contents($out) : $contents;
        } finally {
            @unlink($in);
            @unlink($out);
        }
    }

    private function binary(): string
    {
        return (string) config('conversion.linearize.binary');
    }
}
