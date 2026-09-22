<?php

namespace App\Console\Commands;

use App\Support\Conversion\Drivers\LocalCliDriver;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * One-time setup so an admin never has to open a terminal again to use
 * their own CPU for conversions: registers a Windows scheduled task that
 * starts `conversion:work-local` hidden, in the background, every time
 * this machine logs in — and starts it immediately too. After this runs
 * once, clicking "Use this device" on the dashboard is enough by itself.
 */
class InstallLocalConversionWorker extends Command
{
    protected $signature = 'conversion:install-local-worker';

    protected $description = 'Set this device up to always run the local conversion worker in the background (no more manual commands)';

    private const TASK_NAME = 'JupyterDocsLocalConversionWorker';

    public function handle(LocalCliDriver $driver): int
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->components->error(
                'This installer only knows how to set up Windows so far. '.
                'On macOS/Linux, run `php artisan conversion:work-local` in a terminal '.
                'you leave open, or wrap it in your own launchd/systemd service.'
            );

            return self::FAILURE;
        }

        if (! $driver->isAvailable()) {
            $this->components->warn(
                'LibreOffice was not found on this device yet — installing the background '.
                'worker anyway, but it will fail until LibreOffice is installed. '.
                'On Windows: winget install --id TheDocumentFoundation.LibreOffice'
            );
        }

        $vbsPath = storage_path('app/local-conversion-worker.vbs');
        $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(base_path('artisan')).' conversion:work-local';

        // wscript + a "run hidden" VBScript wrapper is the standard trick
        // for launching a console command from Task Scheduler with no
        // window flashing on screen.
        file_put_contents($vbsPath, implode("\r\n", [
            'Set WshShell = CreateObject("WScript.Shell")',
            'WshShell.Run "'.addslashes($command).'", 0, False',
        ]));

        $create = new Process([
            'schtasks', '/create', '/tn', self::TASK_NAME,
            '/tr', 'wscript.exe "'.$vbsPath.'"',
            '/sc', 'onlogon', '/rl', 'limited', '/f',
        ]);
        $create->run();

        if (! $create->isSuccessful()) {
            $this->components->error('Could not register the scheduled task: '.trim($create->getErrorOutput()));

            return self::FAILURE;
        }

        $this->components->info('Background worker installed — it will start automatically every time you log in to this device.');

        $start = new Process(['schtasks', '/run', '/tn', self::TASK_NAME]);
        $start->run();

        if ($start->isSuccessful()) {
            $this->components->info('Started it now too, so it\'s already running.');
        } else {
            $this->components->warn('Installed, but couldn\'t start it immediately — it will start next time you log in, or run `conversion:work-local` by hand for now.');
        }

        $this->newLine();
        $this->line('To remove it later: <comment>php artisan conversion:uninstall-local-worker</comment>');

        return self::SUCCESS;
    }
}
