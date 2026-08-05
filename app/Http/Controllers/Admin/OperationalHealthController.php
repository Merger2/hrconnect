<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportExportRun;
use App\Models\SystemBackupRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OperationalHealthController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.operational-health', [
            'health' => $this->collectHealth(),
        ]);
    }

    /**
     * Assemble the full operational-health dataset consumed by the blade view.
     *
     * @return array<string, mixed>
     */
    private function collectHealth(): array
    {
        $database = $this->checkDatabase();
        $disk = $this->checkDisk();
        $queue = $this->checkQueue();
        $backup = $this->checkBackup();
        $importExport = $this->checkImportExport();
        $hrCompliance = $this->checkHrCompliance();

        $alerts = [];

        if (! $database['ok']) {
            $alerts[] = ['code' => 'database_down', 'level' => 'critical', 'message' => __('Database connectivity check failed.')];
        }

        if ($queue['heartbeat_stale']) {
            $alerts[] = ['code' => 'queue_stale', 'level' => 'critical', 'message' => __('Queue worker heartbeat is stale — jobs may not be processed.')];
        }

        if ($queue['scheduler_stale']) {
            $alerts[] = ['code' => 'scheduler_stale', 'level' => 'critical', 'message' => __('Scheduler heartbeat is stale — scheduled tasks may not run.')];
        }

        if (! $disk['writable']) {
            $alerts[] = ['code' => 'storage_not_writable', 'level' => 'critical', 'message' => __('Storage directory is not writable.')];
        } elseif ($disk['used_percent'] !== null && $disk['used_percent'] >= 90) {
            $alerts[] = ['code' => 'disk_low', 'level' => 'warning', 'message' => __('Disk usage is above 90%.')];
        }

        if (! $backup['file_present']) {
            $alerts[] = ['code' => 'backup_missing', 'level' => 'warning', 'message' => __('No backup file found for the current backup run.')];
        }

        $status = empty($alerts) ? 'ok' : 'needs_attention';

        return [
            'status' => $status,
            'alerts' => $alerts,
            'app_version' => config('app.version', app()->version()),
            'php_version' => PHP_VERSION,
            'database_driver' => config('database.default'),
            'database_version' => $database['version'],
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_connection' => config('queue.default'),
            'database' => $database,
            'queue_heartbeat_at' => $queue['heartbeat_at'],
            'scheduler_heartbeat_at' => $queue['scheduler_heartbeat_at'],
            'queue_backlog_count' => $queue['backlog'],
            'failed_jobs_count' => $queue['failed'],
            'storage_writable' => $disk['writable'],
            'disk_free_human' => $disk['free_human'],
            'disk_total_human' => $disk['total_human'],
            'disk_used_percent' => $disk['used_percent'],
            'import_export' => $importExport,
            'hr_compliance' => $hrCompliance,
            'backup' => $backup,
            'realtime' => [
                'reverb_enabled' => config('broadcasting.default') === 'reverb',
                'broadcast_connection' => config('broadcasting.default'),
                'polling_fallback' => config('broadcasting.polling_interval', 30).'s',
            ],
            'license' => [
                'payroll_locked' => false,
                'reporting_locked' => false,
                'system_backup_locked' => false,
            ],
            'tables' => $this->tableSizes(),
        ];
    }

    /**
     * @return array{ok:bool,latency_ms:?int,error:?string,version:?string}
     */
    private function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            $version = DB::selectOne('select version() as v');
            $latency = (int) round((microtime(true) - $start) * 1000);

            return [
                'ok' => true,
                'latency_ms' => $latency,
                'error' => null,
                'version' => $version->v ?? null,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'latency_ms' => null,
                'error' => $e->getMessage(),
                'version' => null,
            ];
        }
    }

    /**
     * @return array{writable:bool,free_human:string,total_human:string,used_percent:?int}
     */
    private function checkDisk(): array
    {
        $path = storage_path('app');
        $writable = is_writable($path);

        $free = @disk_free_space($path);
        $total = @disk_total_space($path);

        return [
            'writable' => $writable,
            'free_human' => $free !== false ? $this->formatBytes($free) : __('Unknown'),
            'total_human' => $total !== false ? $this->formatBytes($total) : __('Unknown'),
            'used_percent' => ($free !== false && $total !== false && $total > 0)
                ? (int) round((($total - $free) / $total) * 100)
                : null,
        ];
    }

    /**
     * @return array{heartbeat_at:?string,heartbeat_stale:bool,scheduler_heartbeat_at:?string,scheduler_stale:bool,backlog:int,failed:int}
     */
    private function checkQueue(): array
    {
        $heartbeat = Cache::get('health:queue_heartbeat_at');
        $scheduler = Cache::get('health:scheduler_heartbeat_at');

        return [
            'heartbeat_at' => $heartbeat,
            'heartbeat_stale' => $heartbeat === null || Carbon::parse($heartbeat)->lt(now()->subMinutes(10)),
            'scheduler_heartbeat_at' => $scheduler,
            'scheduler_stale' => $scheduler === null || Carbon::parse($scheduler)->lt(now()->addMinutes(10)),
            'backlog' => $this->queueBacklog(),
            'failed' => $this->failedJobs(),
        ];
    }

    private function queueBacklog(): int
    {
        try {
            return (int) DB::table('jobs')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function failedJobs(): int
    {
        try {
            return (int) DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array{file_present:bool,last_success_at:?string,last_failed_at:?string,checksum_matches_meta:?bool,checksum_sha256:?string}
     */
    private function checkBackup(): array
    {
        try {
            $latest = SystemBackupRun::query()
                ->whereNotNull('completed_at')
                ->latest('completed_at')
                ->first();

            $lastFailed = SystemBackupRun::query()
                ->whereNotNull('failed_at')
                ->latest('failed_at')
                ->first();

            $disk = config('backup.backup.destination.disks')[0] ?? 'backups';

            $filePresent = $latest !== null && filled($latest->file_name)
                && Storage::disk($disk)->exists($latest->file_name);

            // Q1: `checksum_sha256` BUKAN kolom SystemBackupRun (kolom: meta,
            // file_name, dst — lihat migration 2026_07_21_000016). Signature
            // backup HMAC disimpan DI DALAM file dump (SystemBackupService::
            // signDatabaseBackup, baris `-- APP_BACKUP_SIGNATURE:`), bukan di DB.
            // Hitung sha256 file nyata saat file ada — hilangkan null-silent.
            $checksum = null;
            if ($filePresent) {
                $checksum = hash_file('sha256', Storage::disk($disk)->path($latest->file_name)) ?: null;
            }

            // completed_at/failed_at ber-cast datetime — anotasi utk PHPStan
            // (tanpa IdeHelper mixin, casts() tidak terbaca larastan).
            /** @var Carbon|null $completedAt */
            $completedAt = $latest?->completed_at;
            /** @var Carbon|null $failedAt */
            $failedAt = $lastFailed?->failed_at;

            return [
                'file_present' => $filePresent,
                'last_success_at' => $completedAt?->toIso8601String(),
                'last_failed_at' => $failedAt?->toIso8601String(),
                'checksum_matches_meta' => null,
                'checksum_sha256' => $checksum,
            ];
        } catch (\Throwable) {
            return [
                'file_present' => false,
                'last_success_at' => null,
                'last_failed_at' => null,
                'checksum_matches_meta' => null,
                'checksum_sha256' => null,
            ];
        }
    }

    /**
     * @return array{queued:int,running:int,failed:int,last_completed_at:?string}
     */
    private function checkImportExport(): array
    {
        try {
            return [
                'queued' => (int) ImportExportRun::query()->where('status', 'queued')->count(),
                'running' => (int) ImportExportRun::query()->where('status', 'running')->count(),
                'failed' => (int) ImportExportRun::query()->where('status', 'failed')->count(),
                'last_completed_at' => ImportExportRun::query()
                    ->where('status', 'completed')
                    ->latest('updated_at')
                    ->value('updated_at')?->toIso8601String(),
            ];
        } catch (\Throwable) {
            return ['queued' => 0, 'running' => 0, 'failed' => 0, 'last_completed_at' => null];
        }
    }

    /**
     * @return array{probation_due:int,contract_due:int,incomplete_profiles:int,overdue_hr_tasks:int,auto_disable_due:int}
     */
    private function checkHrCompliance(): array
    {
        try {
            return [
                'probation_due' => (int) DB::table('employees')
                    ->whereNotNull('probation_ends_at')
                    ->where('probation_ends_at', '<', now()->addDays(14))
                    ->count(),
                'contract_due' => (int) DB::table('employees')
                    ->whereNotNull('contract_ends_at')
                    ->where('contract_ends_at', '<', now()->addDays(30))
                    ->count(),
                'incomplete_profiles' => (int) DB::table('employees')
                    ->whereNull('phone')
                    ->count(),
                'overdue_hr_tasks' => (int) DB::table('hr_checklist_tasks')
                    ->whereNull('completed_at')
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now())
                    ->count(),
                'auto_disable_due' => (int) DB::table('employees')
                    ->whereNotNull('account_auto_disable_at')
                    ->where('account_auto_disable_at', '<', now()->addDays(7))
                    ->count(),
            ];
        } catch (\Throwable) {
            return ['probation_due' => 0, 'contract_due' => 0, 'incomplete_profiles' => 0, 'overdue_hr_tasks' => 0, 'auto_disable_due' => 0];
        }
    }

    /**
     * @return array<int, array{name:string,rows:?int,size:string}>
     */
    private function tableSizes(): array
    {
        try {
            $rows = DB::select('
                SELECT
                    relname AS name,
                    n_live_tup AS rows,
                    pg_size_pretty(pg_total_relation_size(relid)) AS size
                FROM pg_stat_user_tables
                ORDER BY pg_total_relation_size(relid) DESC
                LIMIT 12
            ');

            return array_map(static fn ($row): array => [
                'name' => $row->name,
                'rows' => $row->rows,
                'size' => $row->size,
            ], $rows);
        } catch (\Throwable) {
            return [];
        }
    }

    private function formatBytes(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i];
    }
}
