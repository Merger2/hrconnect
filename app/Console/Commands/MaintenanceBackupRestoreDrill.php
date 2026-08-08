<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\SystemBackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('maintenance:backup-restore-drill {--backup= : Path relatif backup .sql di local disk; default = backup terbaru}')]
#[Description('Restore backup database terbaru ke database sementara untuk membuktikan restore berjalan (RTO drill; butuh role dengan CREATEDB via DB_DRILL_USERNAME/PASSWORD)')]
class MaintenanceBackupRestoreDrill extends Command
{
    public function handle(SystemBackupService $service): int
    {
        try {
            $result = $service->runRestoreDrill($this->option('backup'));

            $this->info('RESTORE DRILL PASSED');
            $this->line('  File: '.$result['filename']);
            $this->line('  Temp DB: '.$result['temp_database'].' (di-drop setelah verifikasi)');
            $this->line('  Durasi: '.$result['duration_seconds'].' detik');

            foreach ($result['row_counts'] as $table => $count) {
                $this->line("  {$table}: {$count} rows");
            }

            $this->info('Backup database dapat direstore — RTO drill sukses.');

            Log::info('Backup restore drill passed', $result);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Restore drill FAILED: '.$e->getMessage());
            report($e);

            return self::FAILURE;
        }
    }
}
