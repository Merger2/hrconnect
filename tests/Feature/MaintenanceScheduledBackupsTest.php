<?php

use App\Support\SystemBackupService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    // Bersihkan binding mock antar test — tanpa ini, HTTP request berikutnya
    // (health) tetap me-resolve mock dari test sebelumnya (unstubbed -> null
    // -> selalu 'fresh').
    app()->forgetInstance(SystemBackupService::class);
});

test('scheduled backup command creates signed database backup and reports success', function () {
    $service = mock(SystemBackupService::class);
    $service->shouldReceive('createDatabaseBackup')
        ->once()
        ->andReturn([
            'filename' => 'backup-2026-08-08-02-00-00.sql',
            'path' => 'maintenance-backups/database/backup-2026-08-08-02-00-00.sql',
            'size_bytes' => 4096,
        ]);
    app()->instance(SystemBackupService::class, $service);

    $this->artisan('maintenance:scheduled-backups')
        ->expectsOutputToContain('Scheduled backup completed')
        ->assertExitCode(0);

    // Jejak audit: run terjadwal tercatat di activitylog spatie (pengganti
    // SystemBackupRun yang butuh user admin untuk jalur UI).
    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'backup',
        'description' => 'Scheduled database backup completed (maintenance:scheduled-backups).',
    ]);
});

test('scheduled backup command fails cleanly when the backup service throws', function () {
    $service = mock(SystemBackupService::class);
    $service->shouldReceive('createDatabaseBackup')
        ->once()
        ->andThrow(new RuntimeException('pg_dump failed'));
    app()->instance(SystemBackupService::class, $service);

    $this->artisan('maintenance:scheduled-backups')
        ->expectsOutputToContain('Scheduled backup gagal')
        ->assertExitCode(1);

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'backup',
        'description' => 'Scheduled database backup FAILED (maintenance:scheduled-backups).',
    ]);
});

// Health tests memakai partial mock karena Storage::fake('local') (in-memory)
// selalu melaporkan lastModified = "now" — tidak bisa mensimulasikan umur file
// secara nyata. Logika nyata latestBackupHealthIssue() (no-backup, threshold)
// diverifikasi via dev storage (health issue: NULL) dan unit yang sama di atas.

test('health endpoint reports fresh when the latest backup is recent', function () {
    $service = mock(SystemBackupService::class)->makePartial();
    $service->shouldReceive('latestBackupHealthIssue')->once()->andReturnNull();
    app()->instance(SystemBackupService::class, $service);

    $this->get('/api/v1/health')
        ->assertOk()
        ->assertJsonPath('services.backup', 'fresh')
        ->assertJsonPath('status', 'ok');
});

test('health endpoint reports stale when the latest backup is older than the threshold', function () {
    $service = mock(SystemBackupService::class)->makePartial();
    $service->shouldReceive('latestBackupHealthIssue')
        ->once()
        ->andReturn('Latest database backup (backup-2026-08-01.sql) is 72.0 hours old.');
    app()->instance(SystemBackupService::class, $service);

    $this->get('/api/v1/health')
        ->assertStatus(503)
        ->assertJsonPath('services.backup', 'stale')
        ->assertJsonPath('status', 'degraded');
});

test('health endpoint reports stale when no backup exists yet', function () {
    $service = mock(SystemBackupService::class)->makePartial();
    $service->shouldReceive('latestBackupHealthIssue')
        ->once()
        ->andReturn('No database backup exists in maintenance-backups/database.');
    app()->instance(SystemBackupService::class, $service);

    $this->get('/api/v1/health')
        ->assertStatus(503)
        ->assertJsonPath('services.backup', 'stale')
        ->assertJsonPath('status', 'degraded');
});
