<?php

use App\Livewire\Admin\SystemMaintenance;
use App\Support\SystemBackupService;

function verifyBackupSqlForTest(string $sql): string
{

    $component = new SystemMaintenance;
    $method = new ReflectionMethod(SystemMaintenance::class, 'verifiedBackupSql');
    $method->setAccessible(true);

    return $method->invoke($component, $sql);
}

test('database restore accepts only signed application backups', function () {
    $sql = "-- Absensi GPS & Enterprise Database Backup\nSET FOREIGN_KEY_CHECKS=0;\nSET FOREIGN_KEY_CHECKS=1;\n";
    $signature = hash_hmac('sha256', $sql, config('app.key'));

    expect(verifyBackupSqlForTest($sql."\n-- APP_BACKUP_SIGNATURE: {$signature}\n"))->toBe($sql);
});

test('database restore rejects unsigned or modified sql backups', function () {
    $sql = "-- Absensi GPS & Enterprise Database Backup\nSET FOREIGN_KEY_CHECKS=0;\n";
    $signature = hash_hmac('sha256', $sql, config('app.key'));

    expect(fn () => verifyBackupSqlForTest($sql))->toThrow(Exception::class);
    expect(fn () => verifyBackupSqlForTest($sql."DROP TABLE users;\n-- APP_BACKUP_SIGNATURE: {$signature}\n"))->toThrow(Exception::class);
});

test('system generated database backup is signed and passes restore verification', function () {
    $service = app(SystemBackupService::class);

    $tmpFile = tempnam(sys_get_temp_dir(), 'hrconnect-backup-');
    $sql = "-- Absensi GPS & Enterprise Database Backup\nSET FOREIGN_KEY_CHECKS=0;\nSET FOREIGN_KEY_CHECKS=1;\n";
    file_put_contents($tmpFile, $sql);

    $method = new ReflectionMethod(SystemBackupService::class, 'signDatabaseBackup');
    $method->setAccessible(true);
    $method->invoke($service, $tmpFile);

    // Backend roundtrip: dump yang dihasilkan sistem harus lolos verifikasi restore.
    expect(verifyBackupSqlForTest(file_get_contents($tmpFile)))->toBe($sql);

    // Tampered content harus ditolak setelah penandatanganan.
    file_put_contents($tmpFile, file_get_contents($tmpFile)."DROP TABLE users;\n");
    expect(fn () => verifyBackupSqlForTest(file_get_contents($tmpFile)))->toThrow(Exception::class);

    @unlink($tmpFile);
});
