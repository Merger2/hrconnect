<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SystemBackupService
{
    /**
     * Dump the PostgreSQL database to the local maintenance-backups disk.
     *
     * Credentials come from the app's pgsql connection config (never raw
     * shell interpolation); the password is passed via a 0600 .pgpass file
     * so it never appears on the command line.
     *
     * @return array{filename: string, path: string, size_bytes: int}
     */
    public function createDatabaseBackup(): array
    {
        $fileName = 'backup-'.now()->format('Y-m-d-H-i-s').'.sql';
        $path = 'maintenance-backups/database/'.$fileName;
        $absolutePath = Storage::disk('local')->path($path);

        File::ensureDirectoryExists(dirname($absolutePath));

        $dbUser = (string) config('database.connections.pgsql.username');
        $dbHost = (string) config('database.connections.pgsql.host');
        $dbPort = (string) config('database.connections.pgsql.port');
        $dbName = (string) config('database.connections.pgsql.database');
        $dbPass = (string) config('database.connections.pgsql.password');

        $pgpassFile = sys_get_temp_dir().'/hrconnect-pgpass-'.bin2hex(random_bytes(6));
        file_put_contents($pgpassFile, "{$dbHost}:{$dbPort}:{$dbName}:{$dbUser}:{$dbPass}");
        chmod($pgpassFile, 0600);

        try {
            $command = sprintf(
                'PGPASSFILE=%s pg_dump --no-owner --no-acl --clean --if-exists -U %s -h %s -p %s -d %s -f %s 2>&1',
                escapeshellarg($pgpassFile),
                escapeshellarg($dbUser),
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbName),
                escapeshellarg($absolutePath)
            );

            $output = [];
            $exitCode = 0;
            exec($command, $output, $exitCode);

            if ($exitCode !== 0 || ! is_file($absolutePath)) {
                File::delete($absolutePath);

                throw new RuntimeException('Database dump failed: '.implode("\n", array_slice($output, -10)));
            }

            $this->signDatabaseBackup($absolutePath);
        } finally {
            File::delete($pgpassFile);
        }

        return [
            'filename' => $fileName,
            'path' => $path,
            'size_bytes' => (int) Storage::disk('local')->size($path),
        ];
    }

    /**
     * Append an HMAC-SHA256 signature line to a freshly dumped SQL backup so
     * that SystemMaintenance::verifiedBackupSql() can authenticate it during
     * restore. The signature covers the dump content WITHOUT the signature
     * line itself (mirror of the verification regex in SystemMaintenance).
     */
    protected function signDatabaseBackup(string $absolutePath): void
    {
        $content = file_get_contents($absolutePath);

        if ($content === false) {
            throw new RuntimeException("Could not read database dump at {$absolutePath} to sign it.");
        }

        $signature = hash_hmac('sha256', $content, (string) config('app.key'));

        file_put_contents(
            $absolutePath,
            $content."\n-- APP_BACKUP_SIGNATURE: {$signature}\n"
        );
    }

    /**
     * Archive the application source into a zip file on the local
     * maintenance-backups disk (vendor/node_modules/.git excluded).
     *
     * @return array{filename: string, path: string, size_bytes: int}
     */
    public function createApplicationBackup(): array
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new RuntimeException('ZipArchive is not available on this server.');
        }

        $fileName = 'backup-'.now()->format('Y-m-d-H-i-s').'.zip';
        $path = 'maintenance-backups/application/'.$fileName;
        $absolutePath = Storage::disk('local')->path($path);

        File::ensureDirectoryExists(dirname($absolutePath));

        $zip = new \ZipArchive;

        if ($zip->open($absolutePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create application backup archive.');
        }

        $excluded = [
            'vendor',
            'node_modules',
            '.git',
            'storage/app/backups',
            'storage/framework',
            'storage/logs',
        ];

        try {
            $base = base_path();
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                $relative = ltrim(substr($file->getPathname(), strlen($base)), '/\\');
                $segments = explode('/', str_replace('\\', '/', $relative));

                if (in_array($segments[0] ?? '', $excluded, true)) {
                    continue;
                }

                $zip->addFile($file->getPathname(), $relative);
            }
        } catch (\Throwable $e) {
            throw new RuntimeException('Application backup failed: '.$e->getMessage(), 0, $e);
        } finally {
            $zip->close();
        }

        return [
            'filename' => $fileName,
            'path' => $path,
            'size_bytes' => (int) Storage::disk('local')->size($path),
        ];
    }

    /**
     * Return the path (relative to the local disk) of the most recently
     * modified database backup under maintenance-backups/database, or null
     * when no backup exists yet.
     */
    public function findLatestDatabaseBackup(): ?string
    {
        $files = array_values(array_filter(
            Storage::disk('local')->files('maintenance-backups/database'),
            fn (string $path): bool => str_ends_with($path, '.sql')
        ));

        if ($files === []) {
            return null;
        }

        usort(
            $files,
            fn (string $a, string $b): int => Storage::disk('local')->lastModified($b) <=> Storage::disk('local')->lastModified($a)
        );

        return $files[0];
    }

    /**
     * Verify the HMAC-SHA256 signature appended by signDatabaseBackup() and
     * return the SQL content without the signature line. Single source of
     * truth for the SystemMaintenance restore flow and the restore drill.
     *
     * @throws RuntimeException when the signature line is missing or invalid.
     */
    public function verifyDatabaseBackup(string $sql): string
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
     * Restore-drill: prove the latest signed database backup can actually be
     * restored by replaying it into a throwaway PostgreSQL database, verifying
     * row counts on core tables, then dropping the temporary database. Never
     * touches the production database (only a temp DB that is dropped in the
     * finally block, so a failed drill cannot leak state).
     *
     * Scope: pipeline `maintenance-backups` (signed, SystemBackupService /
     * RunSystemBackup). Backup harian spatie (`backup:run --only-db`,
     * storage/app/backups) adalah pipeline terpisah yang tidak ter-signing
     * HMAC — di luar cakupan drill ini (lihat PROGRESS.md).
     *
     * @param  string|null  $backupPath  Relative path on the local disk; defaults to the latest backup.
     * @return array{filename: string, temp_database: string, duration_seconds: float, row_counts: array<string, int>}
     *
     * @throws RuntimeException on any failure — the temporary database is always cleaned up.
     */
    public function runRestoreDrill(?string $backupPath = null): array
    {
        $backupPath ??= $this->findLatestDatabaseBackup();

        if ($backupPath === null) {
            throw new RuntimeException('No database backup found in maintenance-backups/database.');
        }

        $sql = $this->verifyDatabaseBackup((string) Storage::disk('local')->get($backupPath));

        $dbHost = (string) config('database.connections.pgsql.host');
        $dbPort = (string) config('database.connections.pgsql.port');
        // Role drill opsional harus punya CREATEDB (membuat + drop DB sementara);
        // fallback ke kredensial aplikasi bila tidak dikonfigurasi.
        $dbUser = (string) (config('database.connections.pgsql.drill_username') ?: config('database.connections.pgsql.username'));
        $dbPass = (string) (config('database.connections.pgsql.drill_password') ?: config('database.connections.pgsql.password'));

        // Koneksi maintenance: DB aplikasi sendiri — CREATE/DROP DATABASE valid
        // dari koneksi DB mana pun, dan DB aplikasi dijamin selalu ada.
        $maintenanceDb = (string) config('database.connections.pgsql.database');
        $tempDb = 'hrconnect_drill_'.now()->format('Ymd_His').'_'.bin2hex(random_bytes(2));
        $tmpDir = sys_get_temp_dir().'/hrconnect-drill-'.bin2hex(random_bytes(6));
        $sqlFile = $tmpDir.'/restore.sql';
        $pgpassFile = $tmpDir.'/.pgpass';

        $started = microtime(true);

        try {
            File::ensureDirectoryExists($tmpDir, 0700);
            file_put_contents($sqlFile, $sql);
            // Entri pgpass untuk maintenance DB (CREATE/DROP) dan temp DB (restore).
            file_put_contents(
                $pgpassFile,
                "{$dbHost}:{$dbPort}:{$maintenanceDb}:{$dbUser}:{$dbPass}\n".
                "{$dbHost}:{$dbPort}:{$tempDb}:{$dbUser}:{$dbPass}"
            );
            chmod($pgpassFile, 0600);

            try {
                $this->runPsql('CREATE DATABASE '.$tempDb, $maintenanceDb, $pgpassFile, $dbHost, $dbPort, $dbUser);
            } catch (\Throwable $e) {
                throw new RuntimeException(
                    'Cannot create temporary drill database: '.$e->getMessage().
                    ' — the drill role needs CREATEDB privilege (set DB_DRILL_USERNAME/DB_DRILL_PASSWORD or grant CREATEDB to the app role).',
                    0,
                    $e
                );
            }
            $this->runPsqlRestoreFromFile($sqlFile, $tempDb, $pgpassFile, $dbHost, $dbPort, $dbUser);

            $rowCounts = [];
            foreach (['users', 'employees'] as $table) {
                $output = $this->runPsql("SELECT count(*) FROM {$table};", $tempDb, $pgpassFile, $dbHost, $dbPort, $dbUser);
                $rowCounts[$table] = $this->extractRowCount($output);
            }
        } finally {
            $this->dropDatabaseQuietly($tempDb, $maintenanceDb, $pgpassFile, $dbHost, $dbPort, $dbUser);
            File::deleteDirectory($tmpDir);
        }

        return [
            'filename' => basename($backupPath),
            'temp_database' => $tempDb,
            'duration_seconds' => round(microtime(true) - $started, 2),
            'row_counts' => $rowCounts,
        ];
    }

    /**
     * @param  list<string>  $output
     */
    protected function extractRowCount(array $output): int
    {
        foreach ($output as $line) {
            if (preg_match('/^\s*(\d+)\s*$/', (string) $line, $matches)) {
                return (int) $matches[1];
            }
        }

        throw new RuntimeException('Could not parse row count from psql output: '.implode("\n", $output));
    }

    /**
     * @return list<string>
     *
     * @throws RuntimeException when psql exits non-zero.
     */
    protected function runPsql(string $sql, string $dbName, string $pgpassFile, string $dbHost, string $dbPort, string $dbUser): array
    {
        $command = sprintf(
            'PGPASSFILE=%s psql -v ON_ERROR_STOP=1 -U %s -h %s -p %s -d %s -c %s 2>&1',
            escapeshellarg($pgpassFile),
            escapeshellarg($dbUser),
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbName),
            escapeshellarg($sql)
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException('psql failed: '.implode("\n", array_slice($output, -10)));
        }

        return $output;
    }

    /**
     * @throws RuntimeException when psql restore fails.
     */
    protected function runPsqlRestoreFromFile(string $sqlFile, string $dbName, string $pgpassFile, string $dbHost, string $dbPort, string $dbUser): void
    {
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
    }

    protected function dropDatabaseQuietly(string $tempDb, string $maintenanceDb, string $pgpassFile, string $dbHost, string $dbPort, string $dbUser): void
    {
        try {
            $this->runPsql('DROP DATABASE IF EXISTS '.$tempDb.' WITH (FORCE);', $maintenanceDb, $pgpassFile, $dbHost, $dbPort, $dbUser);
        } catch (\Throwable $e) {
            // Cleanup failure must not mask the primary drill result; it is
            // surfaced through the log instead (no silent degradation).
            Log::warning('Restore drill temp database cleanup failed', [
                'temp_database' => $tempDb,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
