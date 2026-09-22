<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class UninstallLocalConversionWorker extends Command
{
    protected $signature = 'conversion:uninstall-local-worker';

    protected $description = 'Remove the background local conversion worker set up by conversion:install-local-worker';

    private const TASK_NAME = 'JupyterDocsLocalConversionWorker';

    public function handle(): int
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->components->error('There is nothing to uninstall — the background worker is only set up on Windows so far.');

            return self::FAILURE;
        }

        $process = new Process(['schtasks', '/delete', '/tn', self::TASK_NAME, '/f']);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->components->error('Could not remove the scheduled task (it may not be installed): '.trim($process->getErrorOutput()));

            return self::FAILURE;
        }

        @unlink(storage_path('app/local-conversion-worker.vbs'));

        $this->components->info('Background worker removed. It will no longer start automatically when you log in.');

        return self::SUCCESS;
    }
}
