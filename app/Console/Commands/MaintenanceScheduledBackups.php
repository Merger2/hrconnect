<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

#[Signature('maintenance:scheduled-backups')]
#[Description('Run scheduled database backup via Spatie backup:run')]
class MaintenanceScheduledBackups extends Command
{
    public function handle(): int
    {
        try {
            $this->info('Starting scheduled backup...');

            Artisan::call('backup:run', ['--only-db' => true], $this->getOutput());

            $this->info('Scheduled backup completed.');

            Artisan::call('backup:clean');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Scheduled backup gagal: {$e->getMessage()}");
            report($e);

            return self::FAILURE;
        }
    }
}
