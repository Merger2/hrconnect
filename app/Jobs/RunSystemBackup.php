<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SystemBackupRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RunSystemBackup implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = 60;

    public $timeout = 300;

    public function __construct(
        public int $backupId
    ) {}

    public function handle(): void
    {
        $backup = SystemBackupRun::query()->findOrFail($this->backupId);

        $backup->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $fileName = "backup-{$backup->id}-".now()->format('Y-m-d-H-i-s').'.sql';
            $path = "backups/{$fileName}";

            // Run pg_dump via artisan command
            $command = "pg_dump --no-owner --no-acl --clean --if-exists {$backup->database_name}";
            $output = [];
            $returnCode = 0;

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('pg_dump failed with code '.$returnCode);
            }

            $sqlContent = implode("\n", $output);
            Storage::disk('local')->put($path, $sqlContent);

            $backup->update([
                'status' => 'completed',
                'file_path' => $path,
                'file_size' => Storage::disk('local')->size($path),
                'completed_at' => now(),
            ]);

            Log::info('System backup completed', [
                'backup_id' => $backup->id,
                'file' => $fileName,
            ]);
        } catch (\Throwable $e) {
            Log::error('System backup failed', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);

            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }
}
