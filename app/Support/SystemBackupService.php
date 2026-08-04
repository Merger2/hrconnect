<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\File;
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
}
