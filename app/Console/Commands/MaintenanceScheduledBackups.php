<?php

namespace App\Console\Commands;

use App\Support\SystemBackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

#[Signature('maintenance:scheduled-backups')]
#[Description('Run scheduled signed database backup (SystemBackupService) and prune expired artifacts')]
class MaintenanceScheduledBackups extends Command
{
    public function handle(SystemBackupService $service): int
    {
        try {
            $result = $service->createDatabaseBackup();

            $this->info('Scheduled backup completed: '.$result['filename'].' ('.Number::fileSize($result['size_bytes']).')');

            Log::info('Scheduled signed database backup completed', $result);

            // Jejak audit: SystemBackupRun (UI) menolak koneksi tanpa user
            // (BackupSecurityService::assertCanManage), jadi run terjadwal
            // dicatat via activitylog — cukup bagi panel System Maintenance
            // untuk melihat backup terakhir tidak pernah "Never".
            activity('backup')
                ->withProperties(['filename' => $result['filename'], 'size_bytes' => $result['size_bytes']])
                ->log('Scheduled database backup completed (maintenance:scheduled-backups).');

            $removed = $this->pruneOldBackups();

            if ($removed > 0) {
                $this->info("Pruned {$removed} expired backup artifact(s).");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Scheduled backup gagal: '.$e->getMessage());
            Log::error('Scheduled signed database backup failed', [
                'error' => $e->getMessage(),
            ]);
            activity('backup')
                ->withProperties(['error' => $e->getMessage()])
                ->log('Scheduled database backup FAILED (maintenance:scheduled-backups).');
            report($e);

            return self::FAILURE;
        }
    }

    /**
     * Hapus artefak backup .sql yang lebih tua dari retensi (config
     * `backup.retention_days`, default 14) — pengganti spatie backup:clean
     * untuk pipeline maintenance-backups.
     */
    protected function pruneOldBackups(): int
    {
        $retentionDays = (int) config('backup.retention_days', 14);
        $disk = Storage::disk('local');
        $cutoff = now()->subDays($retentionDays)->getTimestamp();

        $removed = 0;

        foreach ($disk->files('maintenance-backups/database') as $file) {
            if (str_ends_with($file, '.sql') && $disk->lastModified($file) < $cutoff) {
                if ($disk->delete($file)) {
                    $removed++;
                }
            }
        }

        if ($removed > 0) {
            Log::info('Pruned expired scheduled backups', [
                'removed' => $removed,
                'retention_days' => $retentionDays,
            ]);
        }

        return $removed;
    }
}
