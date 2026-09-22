<?php

namespace App\Console\Commands;

use App\Support\Conversion\Drivers\LocalCliDriver;
use Illuminate\Console\Command;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

/**
 * Run on an admin's own computer (not the production server) after they
 * click "Convert on this device" in the admin dashboard. It processes the
 * `conversion.local.queue` queue against the same database/storage the
 * live site uses, converting documents with a LibreOffice install on this
 * machine — the "edge computing" path for the conversion backlog.
 */
class WorkLocalConversionQueue extends Command
{
    protected $signature = 'conversion:work-local';

    protected $description = 'Convert queued documents to PDF using this device\'s CPU (requires a local LibreOffice install)';

    public function handle(LocalCliDriver $driver): int
    {
        if (! $driver->isAvailable()) {
            $this->components->error(
                'LibreOffice was not found on this device. Install it, then try again. '.
                'On Windows: winget install --id TheDocumentFoundation.LibreOffice'
            );

            return self::FAILURE;
        }

        $this->components->info('Found LibreOffice at: '.$driver->binary());

        // Easy to point this at the wrong database by mistake (the default
        // .env is local dev, not production) — say plainly what it's
        // actually connected to before it starts touching anything.
        try {
            $database = config('database.connections.'.config('database.default').'.database');
            $host = config('database.connections.'.config('database.default').'.host');
            $this->components->info("Connected to database \"{$database}\" on {$host} (environment: ".app()->environment().').');
        } catch (\Throwable) {
            // Non-essential diagnostic — never block the worker over it.
        }

        if (! app()->environment('production')) {
            $this->components->warn(
                'This is NOT running against production — pass --env=production '.
                '(after setting up .env.production) if you meant to convert the live site\'s backlog.'
            );
        }

        $this->components->info('Watching the local conversion queue. Leave this window open — press Ctrl+C to stop.');

        // The Looping event fires at the top of every polling cycle, even
        // when no job is waiting, so the dashboard's "worker connected"
        // indicator stays accurate whether this machine is idle or busy.
        Event::listen(Looping::class, function () {
            Cache::put('conversion:local-worker:heartbeat', now()->toIso8601String(), now()->addSeconds(20));
        });

        return $this->call('queue:work', [
            '--queue' => config('conversion.local.queue'),
            '--tries' => 1,
            '--timeout' => config('conversion.local.timeout'),
            '--sleep' => 2,
        ]);
    }
}
