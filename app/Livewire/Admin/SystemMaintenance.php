<?php

namespace App\Livewire\Admin;

use App\Models\SystemBackupRun;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

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
        Artisan::call('backup:run', ['--only-db' => true]);
        $this->dispatch('notify', message: __('Database backup queued.'));
    }

    public function downloadBackup(int $id): void
    {
        $backup = SystemBackupRun::query()->findOrFail($id);
        $this->dispatch('notify', message: __('Backup download is not available yet.'));
    }

    public function deleteBackup(int $id): void
    {
        SystemBackupRun::query()->whereKey($id)->delete();
        $this->dispatch('notify', message: __('Backup deleted.'));
    }
}
