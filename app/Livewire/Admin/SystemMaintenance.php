<?php

namespace App\Livewire\Admin;

use App\Models\SystemBackupRun;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
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
        Gate::authorize('manageSystemMaintenance');
    }

    public function render(): View
    {
        return view('livewire.admin.system-maintenance', [
            'backups' => SystemBackupRun::query()->latest()->take(20)->get(),
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
