<?php

namespace App\Support\Conversion\Drivers;

use App\Models\Resource;
use App\Support\Conversion\ConversionDriver;
use App\Support\Conversion\ConversionResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Converts a document to PDF using a LibreOffice binary installed on
 * *this* machine. Only meant to be invoked from the `conversion:work-local`
 * command — i.e. by an admin who has opted to spend their own computer's
 * CPU on the conversion backlog instead of waiting on CloudConvert/
 * Gotenberg. Never wired into the default ConversionManager pipeline that
 * runs on the production server, since that server has no LibreOffice
 * install and shouldn't need one.
 */
class LocalCliDriver implements ConversionDriver
{
    public function isAvailable(): bool
    {
        return $this->binary() !== null;
    }

    public function binary(): ?string
    {
        if ($configured = config('conversion.local.soffice_binary')) {
            return is_file($configured) ? $configured : null;
        }

        foreach ($this->commonInstallPaths() as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return (new ExecutableFinder)->find('soffice');
    }

    public function convert(Resource $resource): ConversionResult
    {
        $binary = $this->binary();

        if (! $binary) {
            return ConversionResult::failure(
                'LibreOffice was not found on this device. Install it (e.g. '.
                '"winget install --id TheDocumentFoundation.LibreOffice") or set '.
                'LOCAL_SOFFICE_BINARY in this machine\'s .env to its soffice path.'
            );
        }

        $sourceDisk = Storage::disk(config('filesystems.resource_disk'));
        $workDir = storage_path('app/local-conversion/'.$resource->id.'-'.uniqid());
        @mkdir($workDir, 0755, true);

        $sourceFile = $workDir.'/source.'.$resource->format;
        $outputFile = $workDir.'/source.pdf';

        try {
            file_put_contents($sourceFile, $sourceDisk->get($resource->file_path));

            $process = new Process([
                $binary,
                '--headless',
                '--norestore',
                '--convert-to', 'pdf',
                '--outdir', $workDir,
                $sourceFile,
            ]);

            $process->setTimeout((int) config('conversion.local.timeout', 120));
            $process->run();

            if (! $process->isSuccessful() || ! is_file($outputFile)) {
                Log::warning('Local LibreOffice conversion failed', [
                    'resource_id' => $resource->id,
                    'exit_code' => $process->getExitCode(),
                    'error' => trim($process->getErrorOutput()) ?: trim($process->getOutput()),
                ]);

                return ConversionResult::failure(
                    trim($process->getErrorOutput()) ?: 'LibreOffice exited without producing a PDF.'
                );
            }

            return ConversionResult::success('local', file_get_contents($outputFile));
        } catch (Throwable $e) {
            Log::warning('Local conversion errored', [
                'resource_id' => $resource->id,
                'error' => $e->getMessage(),
            ]);

            return ConversionResult::failure($e->getMessage());
        } finally {
            $this->cleanUp($workDir);
        }
    }

    private function cleanUp(string $workDir): void
    {
        foreach (glob($workDir.'/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($workDir);
    }

    /**
     * @return list<string>
     */
    private function commonInstallPaths(): array
    {
        return [
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            '/Applications/LibreOffice.app/Contents/MacOS/soffice',
            '/usr/bin/soffice',
            '/usr/local/bin/soffice',
        ];
    }
}
