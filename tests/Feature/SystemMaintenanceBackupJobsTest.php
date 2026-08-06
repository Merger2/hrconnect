<?php

use App\Jobs\RunSystemBackup;
use App\Livewire\Admin\SystemMaintenance;
use App\Models\Role;
use App\Models\SystemBackupRun;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {});

test('authorized maintenance manager can queue database backup jobs from system maintenance', function () {

    Queue::fake();

    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Maintenance Manager Jobs_'.uniqid(),
        'slug' => 'maintenance_manager_jobs_'.uniqid(),
        'description' => 'Can manage maintenance backups.',
        'permission_keys' => [
            'admin.system_maintenance.view',
            'admin.system_maintenance.manage',
        ],
    ]);

    $admin->roles()->sync([$role->id]);
    $this->actingAs($admin);

    Livewire::test(SystemMaintenance::class)
        ->call('queueDatabaseBackupJob')
        ->assertDispatched('success');

    $backupRun = SystemBackupRun::query()->first();

    expect($backupRun)->not->toBeNull()
        ->and($backupRun->type)->toBe('database')
        ->and($backupRun->status)->toBe('queued')
        ->and($backupRun->queue)->toBe('maintenance')
        ->and($backupRun->requested_by_user_id)->toBe($admin->id);

    Queue::assertPushed(RunSystemBackup::class, function (RunSystemBackup $job) use ($backupRun) {
        return $job->backupRunId === $backupRun->id
            && $job->queue === 'maintenance';
    });
});

test('admin with view but without maintenance manage permission cannot queue backup jobs from system maintenance', function () {

    Queue::fake();

    // Seorang admin hanya dengan permission view (bukan manage) tetap bisa
    // membuka halaman system maintenance — SystemBackupRunPolicy::viewAny
    // butuh `admin.system_maintenance.view` — tapi tidak boleh meng-queue
    // backup (Gate::allows('create') butuh `manage` + BackupSecurityService).
    $admin = User::factory()->admin()->create();
    $viewRole = Role::create([
        'name' => 'Maintenance Viewer Jobs_'.uniqid(),
        'slug' => 'maintenance_viewer_jobs_'.uniqid(),
        'description' => 'Can view maintenance only.',
        'permission_keys' => ['admin.system_maintenance.view'],
    ]);
    $admin->roles()->sync([$viewRole->id]);
    $this->actingAs($admin);

    Livewire::test(SystemMaintenance::class)
        ->call('queueApplicationBackupJob')
        ->assertDispatched('error');

    expect(SystemBackupRun::count())->toBe(0);
    Queue::assertNothingPushed();
});
