<?php

namespace App\Livewire\Admin;

use App\Jobs\RunSystemBackup;
use App\Models\SystemBackupRun;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

#[Layout('layouts.app')]
class SystemMaintenance extends Component
{
    public string $cleanupConfirmation = '';

    public bool $backupScheduleEnabled = false;

    public string $backupScheduleType = 'db';

    public string $backupScheduleFrequency = 'daily';

    public string $backupScheduleDay = 'monday';

    public string $backupScheduleTime = '02:00';

    public int $backupRetentionDays = 7;

    public $backupFile = null;

    public string $restoreConfirmation = '';

    public function boot(): void
    {
        Gate::authorize('viewAny', SystemBackupRun::class);
    }

    public function render(): View
    {
        $backups = SystemBackupRun::query()->latest()->take(20)->get();

        $formatBytes = static fn (int|float|null $bytes): string => match (true) {
            $bytes === null => '—',
            $bytes >= 1073741824 => round($bytes / 1073741824, 1).' GB',
            $bytes >= 1048576 => round($bytes / 1048576, 1).' MB',
            $bytes >= 1024 => round($bytes / 1024, 1).' KB',
            default => (string) $bytes.' B',
        };

        $jobSummary = [
            'queued' => $backups->where('status', 'queued')->count(),
            'running' => $backups->where('status', 'running')->count(),
            'completed' => $backups->where('status', 'completed')->count(),
            'failed' => $backups->where('status', 'failed')->count(),
        ];

        $latest = $backups->firstWhere('status', 'completed');

        $backupRuns = $backups->map(fn (SystemBackupRun $run): array => [
            'id' => $run->id,
            'type_label' => Str::headline($run->type),
            'status' => $run->status,
            'created_at_human' => $run->created_at?->diffForHumans() ?? '—',
            'updated_at_human' => $run->updated_at?->diffForHumans() ?? '—',
            'requested_by' => $run->requestedBy?->name,
            'size_human' => $run->size_bytes !== null ? $formatBytes($run->size_bytes) : null,
            'file_name' => $run->file_name,
            'error_message' => $run->error_message,
        ])->values()->all();

        return view('livewire.admin.system-maintenance', [
            'backups' => $backups,
            'backupRuns' => $backupRuns,
            'maintenanceMode' => app()->isDownForMaintenance(),
            'systemStats' => [
                ['label' => __('Backups retained'), 'value' => (string) $backups->count()],
                ['label' => __('Queued backup jobs'), 'value' => (string) $jobSummary['queued']],
                ['label' => __('Cache driver'), 'value' => (string) config('cache.default')],
                ['label' => __('Queue driver'), 'value' => (string) config('queue.default')],
                ['label' => __('Session driver'), 'value' => (string) config('session.driver')],
                ['label' => __('Environment'), 'value' => app()->environment()],
            ],
            'healthChecks' => [
                ['label' => __('Database'), 'value' => __('Connected'), 'status' => 'success'],
                ['label' => __('Cache'), 'value' => Str::upper((string) config('cache.default')), 'status' => 'success'],
                ['label' => __('Queue'), 'value' => Str::upper((string) config('queue.default')), 'status' => 'success'],
                ['label' => __('Storage'), 'value' => Str::upper((string) config('filesystems.default')), 'status' => 'success'],
            ],
            'environmentSummary' => [
                __('Application') => (string) config('app.name'),
                __('Environment') => app()->environment(),
                __('PHP version') => PHP_VERSION,
                __('Laravel version') => app()->version(),
            ],
            'recommendedActions' => [],
            'backupOverview' => [
                'files' => $backups->count(),
                'size' => $formatBytes($backups->sum('size_bytes')),
                'latest_age' => $latest?->completed_at?->diffForHumans() ?? __('Never'),
            ],
            'cleanupTargets' => [],
            'latestBackup' => $latest ? [
                'filename' => $latest->file_name ?? '—',
                'type_label' => Str::headline($latest->type),
                'size_human' => $latest->size_bytes !== null ? $formatBytes($latest->size_bytes) : '—',
                'completed_at_human' => $latest->completed_at?->diffForHumans() ?? '—',
            ] : null,
            'backupJobSummary' => $jobSummary,
            'backupScheduleSummary' => [
                'enabled' => false,
                'next_run_human' => null,
                'type_label' => null,
                'frequency_label' => null,
                'time' => null,
                'retention_days' => null,
                'next_run_relative' => null,
            ],
        ]);
    }

    public function toggleMaintenanceMode(): void
    {
        Artisan::call('up');
        $this->dispatch('notify', message: __('Maintenance mode toggled.'));
    }

    public function clearApplicationCaches(): void
    {
        Artisan::call('optimize:clear');
        $this->dispatch('notify', message: __('Application caches cleared.'));
    }

    public function cleanDatabase(): void
    {
        $this->validate(['cleanupConfirmation' => ['required', 'in:yes']]);
        Artisan::call('import-export-runs:prune-expired', ['--hours' => 12]);
        $this->cleanupConfirmation = '';
        $this->dispatch('notify', message: __('Database cleaned.'));
    }

    public function queueDatabaseBackupJob(): void
    {
        if (! Gate::allows('create', SystemBackupRun::class)) {
            $this->dispatch('error', message: __('You do not have permission to queue database backups.'));

            return;
        }

        $backupRun = SystemBackupRun::create([
            'type' => 'database',
            'status' => 'queued',
            'requested_by_user_id' => auth()->id(),
            'queue' => 'maintenance',
            'file_disk' => 'local',
        ]);

        RunSystemBackup::dispatch($backupRun->id);

        $this->dispatch('success', message: __('Database backup queued.'));
    }

    public function queueApplicationBackupJob(): void
    {
        if (! Gate::allows('create', SystemBackupRun::class)) {
            $this->dispatch('error', message: __('You do not have permission to queue application backups.'));

            return;
        }

        $backupRun = SystemBackupRun::create([
            'type' => 'application',
            'status' => 'queued',
            'requested_by_user_id' => auth()->id(),
            'queue' => 'maintenance',
            'file_disk' => 'local',
        ]);

        RunSystemBackup::dispatch($backupRun->id);

        $this->dispatch('success', message: __('Application backup queued.'));
    }

    public function restoreDatabase(): void
    {
        if (! Gate::allows('restore', SystemBackupRun::class)) {
            $this->dispatch('error', message: __('You do not have permission to restore the database.'));

            return;
        }

        $this->validate([
            'restoreConfirmation' => ['required', 'in:RESTORE'],
            'backupFile' => ['required', 'file', 'max:102400'],
        ]);

        try {
            $sql = $this->verifiedBackupSql($this->backupFile->get());

            $backupRun = SystemBackupRun::create([
                'type' => 'restore',
                'status' => 'running',
                'requested_by_user_id' => auth()->id(),
                'queue' => 'maintenance',
                'file_disk' => 'local',
            ]);

            $this->executePsqlRestore($sql);

            $backupRun->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->reset(['backupFile', 'restoreConfirmation']);

            $this->dispatch('success', message: __('Database restored successfully.'));
        } catch (\Throwable $e) {
            $this->reset(['backupFile', 'restoreConfirmation']);

            // Jangan biarkan run restore menggantung di status 'running' tanpa
            // jejak audit — tandai failed + alasan (temuan code-review).
            if (isset($backupRun)) {
                $backupRun->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'failed_at' => now(),
                ]);
            }

            $this->dispatch('error', message: $e->getMessage());
        }
    }

    /**
     * Verify the HMAC signature appended to an application-generated SQL
     * backup and return the SQL content without the signature line.
     *
     * @throws RuntimeException when the signature line is missing or invalid.
     */
    protected function verifiedBackupSql(string $sql): string
    {
        $pattern = "/\n-- APP_BACKUP_SIGNATURE: ([0-9a-f]{64})\s*$/";

        if (! preg_match($pattern, $sql, $matches)) {
            throw new RuntimeException('Unsigned or malformed backup: missing APP_BACKUP_SIGNATURE.');
        }

        $content = preg_replace($pattern, '', $sql);

        if (! hash_equals(hash_hmac('sha256', $content, (string) config('app.key')), $matches[1])) {
            throw new RuntimeException('Backup signature verification failed; the file may have been tampered with.');
        }

        return $content;
    }

    /**
     * Replay verified SQL into the PostgreSQL database using the app's
     * configured credentials (password passed via a 0600 .pgpass file).
     *
     * @throws RuntimeException when psql fails.
     */
    protected function executePsqlRestore(string $sql): void
    {
        $dbUser = (string) config('database.connections.pgsql.username');
        $dbHost = (string) config('database.connections.pgsql.host');
        $dbPort = (string) config('database.connections.pgsql.port');
        $dbName = (string) config('database.connections.pgsql.database');
        $dbPass = (string) config('database.connections.pgsql.password');

        $tmpDir = sys_get_temp_dir().'/hrconnect-restore-'.bin2hex(random_bytes(6));
        $sqlFile = $tmpDir.'/restore.sql';
        $pgpassFile = $tmpDir.'/.pgpass';

        try {
            File::ensureDirectoryExists($tmpDir, 0700);
            file_put_contents($sqlFile, $sql);
            file_put_contents($pgpassFile, "{$dbHost}:{$dbPort}:{$dbName}:{$dbUser}:{$dbPass}");
            chmod($pgpassFile, 0600);

            $command = sprintf(
                'PGPASSFILE=%s psql -v ON_ERROR_STOP=1 -U %s -h %s -p %s -d %s -f %s 2>&1',
                escapeshellarg($pgpassFile),
                escapeshellarg($dbUser),
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbName),
                escapeshellarg($sqlFile)
            );

            $output = [];
            $exitCode = 0;
            exec($command, $output, $exitCode);

            if ($exitCode !== 0) {
                throw new RuntimeException('Database restore failed: '.implode("\n", array_slice($output, -10)));
            }
        } finally {
            File::deleteDirectory($tmpDir);
        }
    }

    public function downloadBackup(int $id): void
    {
        $backup = SystemBackupRun::query()->findOrFail($id);
        $this->dispatch('notify', message: __('Backup download is not available yet.'));
    }

    public function downloadExistingBackup(int $id): Response
    {
        $backup = SystemBackupRun::query()->findOrFail($id);

        if (! Gate::allows('download', $backup)) {
            $this->dispatch('error', message: __('You do not have permission to download this backup artifact.'));

            return response()->noContent();
        }

        $disk = Storage::disk($backup->file_disk ?: config('filesystems.default'));

        if (! $backup->file_path || ! $disk->exists($backup->file_path)) {
            $this->dispatch('error', message: __('Backup artifact is no longer available on disk.'));

            return response()->noContent();
        }

        $this->dispatch('notify', message: __('Backup download started.'));

        return $disk->download($backup->file_path, $backup->file_name ?: basename($backup->file_path));
    }

    public function deleteBackup(int $id): void
    {
        $backup = SystemBackupRun::query()->find($id);

        if (! $backup || ! Gate::allows('delete', $backup)) {
            $this->dispatch('error', message: __('Tidak memiliki izin untuk menghapus backup.'));

            return;
        }

        $backup->delete();
        $this->dispatch('notify', message: __('Backup deleted.'));
    }
}
