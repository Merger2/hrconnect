<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupRunCommand extends Command
{
    protected $signature = 'hrconnect:backup {--no-files : Skip file backup, only DB}';

    protected $description = 'Create application backup (DB dump + optional files) using tar.gz';

    public function handle(): int
    {
        $dbUser = config('database.connections.pgsql.username');
        $dbHost = config('database.connections.pgsql.host');
        $dbPort = config('database.connections.pgsql.port');
        $dbName = config('database.connections.pgsql.database');
        $dbPass = config('database.connections.pgsql.password');

        $timestamp = date('Y-m-d-H-i-s');
        $backupName = "hrconnect-{$timestamp}";
        $backupDir = storage_path('app/backups');
        $tempDir = "/tmp/hrconnect-backup-{$timestamp}";

        File::ensureDirectoryExists($backupDir);
        File::ensureDirectoryExists($tempDir);

        // 1. Dump database
        $this->info("Dumping database {$dbName}...");
        $dumpFile = "{$tempDir}/database.sql";
        $pgpass = "{$dbHost}:{$dbPort}:{$dbName}:{$dbUser}:{$dbPass}";
        $pgpassFile = "{$tempDir}/.pgpass";
        file_put_contents($pgpassFile, $pgpass);
        chmod($pgpassFile, 0600);

        $cmd = sprintf(
            'PGPASSFILE=%s pg_dump -U %s -h %s -p %s %s -f %s 2>&1',
            escapeshellarg($pgpassFile),
            escapeshellarg($dbUser),
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbName),
            escapeshellarg($dumpFile)
        );

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error('Database dump failed: ' . implode("\n", $output));
            return self::FAILURE;
        }

        $this->info('Database dumped: ' . \Illuminate\Support\Number::fileSize(filesize($dumpFile)));

        // 2. Files (optional)
        if (! $this->option('no-files')) {
            $this->info('Archiving files...');
            $exclude = [
                'vendor',
                'node_modules',
                '.git',
                'storage/app/backups',
                'storage/framework',
                'storage/logs',
            ];
            $excludeArgs = implode(' ', array_map(fn ($e) => "--exclude={$e}", $exclude));
            $tarCmd = sprintf(
                'tar %s -czf %s -C %s . 2>&1',
                $excludeArgs,
                escapeshellarg("{$tempDir}/files.tar.gz"),
                escapeshellarg(base_path())
            );
            exec($tarCmd, $tarOutput, $tarExit);
            if ($tarExit !== 0) {
                $this->warn('File archive had issues: ' . implode("\n", $tarOutput));
            }
        }

        // 3. Combine into final tar.gz
        $finalTar = "{$backupDir}/{$backupName}.tar.gz";
        $combineCmd = sprintf(
            'tar -czf %s -C %s . 2>&1',
            escapeshellarg($finalTar),
            escapeshellarg($tempDir)
        );
        exec($combineCmd, $combineOutput, $combineExit);

        // Cleanup temp
        File::deleteDirectory($tempDir);

        if ($combineExit !== 0) {
            $this->error('Final archive failed: ' . implode("\n", $combineOutput));
            return self::FAILURE;
        }

        $size = \Illuminate\Support\Number::fileSize(filesize($finalTar));
        $this->info("Backup created: {$backupName}.tar.gz ({$size})");
        $this->info("Location: {$finalTar}");

        return self::SUCCESS;
    }
}
