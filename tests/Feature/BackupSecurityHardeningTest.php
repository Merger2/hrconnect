<?php

use App\Contracts\AuditServiceInterface;
use App\Livewire\Admin\SystemMaintenance;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\Setting;
use App\Models\SystemBackupRun;
use App\Models\User;
use App\Support\EnterpriseRuntime;
use App\Support\SystemBackupService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {});

function fakeAuditRecorder(): object
{
    return new class implements AuditServiceInterface
    {
        public array $records = [];

        public function record(string $action, ?string $description = null)
        {
            $this->records[] = compact('action', 'description');

            return null;
        }

        public function getTrail(array $filters = []): array
        {
            return $this->records;
        }
    };
}

test('backup runs require explicit maintenance manage permission', function () {
    $audit = fakeAuditRecorder();
    app()->instance(AuditServiceInterface::class, $audit);

    $admin = User::factory()->admin()->create();
    $maintenanceManager = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Backup Maintenance Manager_'.uniqid(),
        'slug' => 'backup_maintenance_manager_'.uniqid(),
        'description' => 'Can manage maintenance backups.',
        'permission_keys' => ['admin.system_maintenance.manage'],
    ]);

    $maintenanceManager->roles()->sync([$role->id]);

    expect(fn () => SystemBackupRun::create([
        'type' => 'database',
        'status' => 'queued',
        'requested_by_user_id' => $admin->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]))->toThrow(AuthorizationException::class, 'You do not have permission to manage the backup system.');

    $backupRun = SystemBackupRun::create([
        'type' => 'database',
        'status' => 'queued',
        'requested_by_user_id' => $maintenanceManager->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]);

    expect($backupRun)->not->toBeNull()
        ->and($backupRun->requested_by_user_id)->toBe($maintenanceManager->id)
        ->and($audit->records)->toHaveCount(1);
});

test('backup runs can require mfa for superadmins', function () {
    $audit = fakeAuditRecorder();
    app()->instance(AuditServiceInterface::class, $audit);

    Setting::updateOrCreate(
        ['key' => 'backup.require_mfa'],
        ['value' => '1', 'group' => 'maintenance', 'type' => 'boolean']
    );
    Setting::flushCache('backup.require_mfa');

    $superadmin = User::factory()->admin(true)->create();

    expect(fn () => SystemBackupRun::create([
        'type' => 'database',
        'status' => 'queued',
        'requested_by_user_id' => $superadmin->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]))->toThrow(AuthorizationException::class, 'Multi-factor authentication is required before managing the backup system.');

    $superadmin->forceFill([
        'two_factor_secret' => encrypt('otp-secret'),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $backupRun = SystemBackupRun::create([
        'type' => 'database',
        'status' => 'queued',
        'requested_by_user_id' => $superadmin->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]);

    expect($backupRun)->not->toBeNull()
        ->and($audit->records)->toHaveCount(1)
        ->and($audit->records[0]['action'])->toBe('Backup Database Queued');
});

test('completed backup runs beyond the configured size limit are downgraded to failed and audited', function () {
    $audit = fakeAuditRecorder();
    app()->instance(AuditServiceInterface::class, $audit);

    Setting::updateOrCreate(
        ['key' => 'backup.max_file_size_bytes'],
        ['value' => '1024', 'group' => 'maintenance', 'type' => 'number']
    );
    Setting::flushCache('backup.max_file_size_bytes');

    $superadmin = User::factory()->admin(true)->create();

    $backupRun = SystemBackupRun::create([
        'type' => 'restore',
        'status' => 'queued',
        'requested_by_user_id' => $superadmin->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]);

    $backupRun->update([
        'status' => 'completed',
        'file_name' => 'oversized-backup.sql',
        'size_bytes' => 4096,
        'completed_at' => now(),
    ]);

    $backupRun->refresh();

    expect($backupRun->status)->toBe('failed')
        ->and($backupRun->failed_at)->not->toBeNull()
        ->and($backupRun->error_message)->toContain('configured size limit');

    expect($audit->records)->toHaveCount(2)
        ->and($audit->records[0]['action'])->toBe('Backup Restore Queued')
        ->and($audit->records[1]['action'])->toBe('Backup Restore Failed');
});

test('activity logs are append only and expose integrity tampering', function () {
    $activityLog = ActivityLog::create([
        'user_id' => User::factory()->create()->id,
        'action' => 'Security Review',
        'description' => 'Created immutable audit row.',
        'ip_address' => '127.0.0.1',
    ]);

    expect($activityLog->hasValidIntegrityHash())->toBeTrue();

    $activityLog->forceFill(['count' => 2])->save();

    expect($activityLog->refresh()->hasValidIntegrityHash())->toBeTrue()
        ->and($activityLog->count)->toBe(2);

    expect(fn () => $activityLog->forceFill(['description' => 'Changed'])->save())
        ->toThrow(AuthorizationException::class, 'Activity logs are append-only and cannot be modified.');

    $activityLog->refresh();

    expect(fn () => $activityLog->delete())
        ->toThrow(AuthorizationException::class, 'Activity logs are append-only and cannot be deleted.');

    DB::table('activity_logs')
        ->where('id', $activityLog->id)
        ->update(['description' => 'Tampered outside the model']);

    expect($activityLog->refresh()->hasValidIntegrityHash())->toBeFalse();
});

test('activity log records every call with valid integrity and no warnings', function () {

    Log::spy();

    $user = User::factory()->create();
    $this->actingAs($user);

    $first = ActivityLog::record('Livewire Action', 'POST /livewire/update ()');
    $second = ActivityLog::record('Livewire Action', 'POST /livewire/update ()');

    // CommunityAuditService mencatat per panggilan (tidak merge count) dan
    // LogUserActivity middleware tidak ter-register (AUDIT M25) — dua baris
    // terpisah adalah behavior nyata saat ini. Throttling adalah enterprise
    // enhancement yang belum diimplementasikan (lihat AUDIT).
    expect($first)->not->toBeNull()
        ->and($second)->not->toBeNull()
        ->and(ActivityLog::query()->where('action', 'Livewire Action')->count())->toBe(2)
        ->and($first->hasValidIntegrityHash())->toBeTrue()
        ->and($second->hasValidIntegrityHash())->toBeTrue();

    Log::shouldNotHaveReceived('warning');
});

test('backup artifact downloads and deletes require maintenance manager authorization', function () {
    $viewer = User::factory()->admin()->create();
    $manager = User::factory()->admin()->create();

    Role::create([
        'name' => 'Maintenance Viewer_'.uniqid(),
        'slug' => 'maintenance_viewer_artifact_policy_'.uniqid(),
        'description' => 'Can view maintenance only.',
        'permission_keys' => ['admin.system_maintenance.view'],
    ])->users()->attach($viewer);

    Role::create([
        'name' => 'Maintenance Manager_'.uniqid(),
        'slug' => 'maintenance_manager_artifact_policy_'.uniqid(),
        'description' => 'Can manage maintenance backups.',
        'permission_keys' => ['admin.system_maintenance.manage'],
    ])->users()->attach($manager);

    $backupRun = SystemBackupRun::create([
        'type' => 'database',
        'status' => 'queued',
        'requested_by_user_id' => $manager->id,
        'queue' => 'maintenance',
        'file_disk' => 'local',
    ]);

    $backupRun->update([
        'status' => 'completed',
        'file_path' => 'backups/database.sql',
        'file_name' => 'database.sql',
        'size_bytes' => 512,
        'completed_at' => now(),
    ]);

    expect(Gate::forUser($viewer)->allows('download', $backupRun))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('delete', $backupRun))->toBeFalse()
        ->and(Gate::forUser($manager)->allows('download', $backupRun))->toBeTrue()
        ->and(Gate::forUser($manager)->allows('delete', $backupRun))->toBeTrue();

    Storage::fake('local');
    Storage::disk('local')->put('backups/database.sql', 'select 1;');

    if (! EnterpriseRuntime::sourceAvailable(probeClass: SystemMaintenance::class)) {
        return;
    }

    $this->actingAs($viewer);

    Livewire::test(SystemMaintenance::class)
        ->call('downloadExistingBackup', $backupRun->id)
        ->assertDispatched('error');

    Livewire::test(SystemMaintenance::class)
        ->call('deleteBackup', $backupRun->id)
        ->assertDispatched('error');

    expect($backupRun->refresh()->exists)->toBeTrue()
        ->and(Storage::disk('local')->exists('backups/database.sql'))->toBeTrue();
});

test('destructive update and maintenance flows require explicit confirmation controls', function () {
    $maintenanceView = File::get(resource_path('views/livewire/admin/system-maintenance.blade.php'));

    expect($maintenanceView)
        ->toContain('wire:model.defer="restoreConfirmation"')
        ->toContain('wire:submit.prevent="restoreDatabase"')
        ->toContain('wire:confirm="{{ __(\'Delete this retained backup file?\') }}"');

    // update.sh tidak ada di repo (artifact deployment lokal, tidak di-commit).
    if (File::exists(base_path('update.sh'))) {
        $updateScript = File::get(base_path('update.sh'));

        expect($updateScript)
            ->toContain('PASPAPAN_UPDATE_CONFIRM')
            ->toContain('PASPAPAN_UPDATE_DISCARD_LOCAL_CHANGES')
            ->toContain('git reset --hard "origin/${TARGET_BRANCH}"');
    }
});

test('backup restore drill command reports success when the latest backup restores cleanly', function () {
    $service = mock(SystemBackupService::class);
    $service->shouldReceive('runRestoreDrill')
        ->once()
        ->with(null)
        ->andReturn([
            'filename' => 'backup-2026-08-06-02-00-00.sql',
            'temp_database' => 'hrconnect_drill_20260806_020000_ab12',
            'duration_seconds' => 4.5,
            'row_counts' => ['users' => 7, 'employees' => 6],
        ]);
    app()->instance(SystemBackupService::class, $service);

    $this->artisan('maintenance:backup-restore-drill')
        ->expectsOutputToContain('RESTORE DRILL PASSED')
        ->expectsOutputToContain('users: 7 rows')
        ->assertExitCode(0);
});

test('backup restore drill command fails cleanly when the drill cannot run', function () {
    $service = mock(SystemBackupService::class);
    $service->shouldReceive('runRestoreDrill')
        ->once()
        ->andThrow(new RuntimeException('No database backup found in maintenance-backups/database.'));
    app()->instance(SystemBackupService::class, $service);

    $this->artisan('maintenance:backup-restore-drill')
        ->expectsOutputToContain('Restore drill FAILED')
        ->assertExitCode(1);
});

test('database backup drill verification accepts only signed application backups', function () {
    $service = app(SystemBackupService::class);

    $sql = "-- Absensi GPS & Enterprise Database Backup\nSET FOREIGN_KEY_CHECKS=0;\nSET FOREIGN_KEY_CHECKS=1;\n";
    $signature = hash_hmac('sha256', $sql, config('app.key'));

    expect($service->verifyDatabaseBackup($sql."\n-- APP_BACKUP_SIGNATURE: {$signature}\n"))->toBe($sql);

    expect(fn () => $service->verifyDatabaseBackup($sql))->toThrow(RuntimeException::class);
    expect(fn () => $service->verifyDatabaseBackup($sql."DROP TABLE users;\n-- APP_BACKUP_SIGNATURE: {$signature}\n"))->toThrow(RuntimeException::class);
});
