<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SystemBackupRun;
use App\Support\SystemBackupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunSystemBackup implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = 60;

    public $timeout = 300;

    public function __construct(
        public int $backupRunId
    ) {
        $this->queue = 'maintenance';
    }

    public function handle(SystemBackupService $service): void
    {
        $backup = SystemBackupRun::query()->findOrFail($this->backupRunId);

        $backup->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $result = $backup->type === 'application'
                ? $service->createApplicationBackup()
                : $service->createDatabaseBackup();

            $backup->update([
                'status' => 'completed',
                'file_name' => $result['filename'],
                'file_path' => $result['path'],
                'size_bytes' => $result['size_bytes'],
                'completed_at' => now(),
            ]);

            Log::info('System backup completed', [
                'backup_run_id' => $backup->id,
                'file' => $result['filename'],
                'size_bytes' => $result['size_bytes'],
            ]);
        } catch (\Throwable $e) {
            Log::error('System backup failed', [
                'backup_run_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'failed_at' => now(),
            ]);

            throw $e;
        }
    }
}
