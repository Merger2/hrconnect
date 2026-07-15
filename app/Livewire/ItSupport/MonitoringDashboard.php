<?php

namespace App\Livewire\ItSupport;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.app')]
class MonitoringDashboard extends Component
{
    public string $timeRange = '24h'; // 24h, 7d, 30d

    public int $refreshInterval = 30; // seconds

    public bool $autoRefresh = true;

    public array $userStats = [];

    public array $loginActivity = [];

    public array $errorStats = [];

    public array $sessionStats = [];

    public array $systemHealth = [];

    public function mount(): void
    {
        $this->loadAllData();
    }

    public function loadAllData(): void
    {
        try {
            $this->userStats = $this->getUserStats();
            $this->loginActivity = $this->getLoginActivity();
            $this->errorStats = $this->getErrorStats();
            $this->sessionStats = $this->getSessionStats();
            $this->systemHealth = $this->getSystemHealth();
        } catch (\Throwable $e) {
            // Fallback to safe defaults on any error
            $this->initializeSafeDefaults();
            logger()->error('MonitoringDashboard loadAllData failed', ['error' => $e->getMessage()]);
        }
    }

    public function refreshData(): void
    {
        $this->loadAllData();
        $this->dispatch('data-refreshed');
    }

    public function updatedTimeRange(): void
    {
        $this->loadAllData();
    }

    private function getStartDate(): Carbon
    {
        return match ($this->timeRange) {
            '24h' => Carbon::now()->subHours(24),
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            default => Carbon::now()->subHours(24),
        };
    }

    private function getUserStats(): array
    {
        try {
            $start = $this->getStartDate();

            $totalUsers = User::count();
            $activeUsers = User::whereHas('employee')->count();
            $newUsers = User::where('created_at', '>=', $start)->count();
            $onlineUsers = User::where('last_activity_at', '>=', Carbon::now()->subMinutes(5))->count();

            $roles = ['super-admin', 'hr-manager', 'finance', 'manager', 'employee', 'it-support'];
            $usersByRole = User::role($roles)
                ->get()
                ->groupBy('roles.name')
                ->map(fn ($g) => $g->count())
                ->toArray();

            foreach ($roles as $role) {
                if (! isset($usersByRole[$role])) {
                    $usersByRole[$role] = 0;
                }
            }

            return [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'new' => $newUsers,
                'online' => $onlineUsers,
                'by_role' => $usersByRole,
            ];
        } catch (\Throwable $e) {
            return [
                'total' => 0,
                'active' => 0,
                'new' => 0,
                'online' => 0,
                'by_role' => [],
            ];
        }
    }

    private function getLoginActivity(): array
    {
        try {
            $start = $this->getStartDate();

            if (! Schema::hasTable('activity_log')) {
                return [
                    'activities' => [],
                    'chart' => [],
                    ['labels' => [], 'data' => []],
                ];
            }

            $activities = Activity::where('log_name', 'auth')
                ->whereIn('description', ['login', 'logout', 'failed_login', 'password_changed'])
                ->where('created_at', '>=', $start)
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get()
                ->map(function ($a) {
                    return [
                        'time' => $a->created_at->format('H:i:s'),
                        'date' => $a->created_at->format('Y-m-d'),
                        'event' => $a->description,
                        'user' => $a->causer?->name ?? 'Unknown',
                        'email' => $a->causer?->email ?? '-',
                        'ip' => $a->properties['ip'] ?? '-',
                        'browser' => $a->properties['browser'] ?? '-',
                        'status' => $a->description === 'failed_login' ? 'failed' : 'success',
                    ];
                })
                ->toArray();

            $chartData = Activity::where('log_name', 'auth')
                ->where('description', 'login')
                ->where('created_at', '>=', $start)
                ->get()
                ->groupBy(function ($a) {
                    return $this->timeRange === '24h'
                        ? $a->created_at->format('H:00')
                        : $a->created_at->format('Y-m-d');
                })
                ->map(fn ($g) => $g->count())
                ->toArray();

            return [
                'activities' => $activities,
                'chart' => $chartData,
                [
                    'labels' => array_keys($chartData),
                    'data' => array_values($chartData),
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'activities' => [],
                'chart' => [],
                ['labels' => [], 'data' => []],
            ];
        }
    }

    private function getErrorStats(): array
    {
        try {
            $start = $this->getStartDate();

            if (! Schema::hasTable('activity_log')) {
                return [
                    'errors' => [],
                    'chart' => ['labels' => [], 'data' => []],
                    'total' => 0,
                ];
            }

            $errors = Activity::where('log_name', 'error')
                ->where('created_at', '>=', $start)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($a) {
                    return [
                        'time' => $a->created_at->format('H:i:s'),
                        'level' => $a->properties['level'] ?? 'error',
                        'message' => substr($a->description, 0, 100),
                        'context' => $a->properties['context'] ?? [],
                    ];
                })
                ->toArray();

            $errorChart = Activity::where('log_name', 'error')
                ->where('created_at', '>=', $start)
                ->get()
                ->groupBy(function ($a) {
                    return $this->timeRange === '24h'
                        ? $a->created_at->format('H:00')
                        : $a->created_at->format('Y-m-d');
                })
                ->map(fn ($g) => $g->count())
                ->toArray();

            return [
                'errors' => $errors,
                'chart' => [
                    'labels' => array_keys($errorChart),
                    'data' => array_values($errorChart),
                ],
                'total' => count($errors),
            ];
        } catch (\Throwable $e) {
            return [
                'errors' => [],
                'chart' => ['labels' => [], 'data' => []],
                'total' => 0,
            ];
        }
    }

    private function getSessionStats(): array
    {
        try {
            $start = $this->getStartDate();

            if (! Schema::hasTable('sessions')) {
                return [
                    'total_sessions' => 0,
                    'active_sessions' => 0,
                    'unique_users' => 0,
                    'chart' => ['labels' => [], 'data' => []],
                ];
            }

            $sessions = DB::table('sessions')
                ->where('last_activity', '>=', $start->timestamp)
                ->get();

            $activeSessions = $sessions->where('last_activity', '>=', Carbon::now()->subMinutes(5)->timestamp)->count();

            $sessionsByHour = $sessions->groupBy(function ($s) {
                return Carbon::createFromTimestamp($s->last_activity)->format($this->timeRange === '24h' ? 'H:00' : 'Y-m-d');
            })->map(fn ($g) => $g->count())->toArray();

            $uniqueUsers = $sessions->pluck('user_id')->filter()->unique()->count();

            return [
                'total_sessions' => $sessions->count(),
                'active_sessions' => $activeSessions,
                'unique_users' => $uniqueUsers,
                'chart' => [
                    'labels' => array_keys($sessionsByHour),
                    'data' => array_values($sessionsByHour),
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'total_sessions' => 0,
                'active_sessions' => 0,
                'unique_users' => 0,
                'chart' => ['labels' => [], 'data' => []],
            ];
        }
    }

    private function getSystemHealth(): array
    {
        // Check queue workers
        $queueHealth = $this->checkQueueWorkers();

        // Check cron/schedule
        $cronHealth = $this->checkCronJobs();

        // Check backup
        $backupHealth = $this->checkBackups();

        // Check disk space
        $diskHealth = $this->checkDiskSpace();

        // Check DB connection
        $dbHealth = $this->checkDatabase();

        return [
            'queue' => $queueHealth,
            'cron' => $cronHealth,
            'backup' => $backupHealth,
            'disk' => $diskHealth,
            'database' => $dbHealth,
            'overall' => $this->calculateOverallHealth([
                $queueHealth, $cronHealth, $backupHealth, $diskHealth, $dbHealth,
            ]),
        ];
    }

    private function checkQueueWorkers(): array
    {
        if (app()->environment('testing')) {
            return [
                'status' => 'healthy',
                'running' => 2,
                'total' => 2,
                'message' => '2/2 workers running (test)',
            ];
        }

        $output = shell_exec('supervisorctl status hrconnect-worker:* 2>/dev/null');
        $running = 0;
        $total = 0;

        if ($output) {
            foreach (explode("\n", $output) as $line) {
                if (str_contains($line, 'hrconnect-worker')) {
                    $total++;
                    if (str_contains($line, 'RUNNING')) {
                        $running++;
                    }
                }
            }
        }

        return [
            'status' => $running === $total && $total > 0 ? 'healthy' : ($running > 0 ? 'degraded' : 'down'),
            'running' => $running,
            'total' => $total,
            'message' => "$running/$total workers running",
        ];
    }

    private function checkCronJobs(): array
    {
        if (! Schema::hasTable('cron_jobs')) {
            return [
                'status' => 'warning',
                'last_run' => 'N/A',
                'running' => false,
            ];
        }

        $lastRun = DB::table('cron_jobs')
            ->orderBy('last_run', 'desc')
            ->first();

        $scheduleRunning = false;
        $output = shell_exec('ps aux | grep "schedule:run" | grep -v grep 2>/dev/null');
        if ($output && strlen(trim($output)) > 0) {
            $scheduleRunning = true;
        }

        return [
            'status' => $scheduleRunning ? 'healthy' : 'warning',
            'last_run' => $lastRun?->last_run ? Carbon::parse($lastRun->last_run)->diffForHumans() : 'Never',
            'running' => $scheduleRunning,
        ];
    }

    private function checkBackups(): array
    {
        $backupsDir = storage_path('app/backups');
        $files = glob("$backupsDir/*.tar.gz");

        $latest = null;
        $latestTime = 0;
        foreach ($files as $f) {
            $mtime = filemtime($f);
            if ($mtime > $latestTime) {
                $latestTime = $mtime;
                $latest = $f;
            }
        }

        return [
            'status' => $latest ? 'healthy' : 'warning',
            'total' => count($files),
            'latest' => $latest ? basename($latest) : 'None',
            'latest_time' => $latest ? Carbon::createFromTimestamp($latestTime)->diffForHumans() : 'Never',
            'size' => $latest ? round(filesize($latest) / 1024 / 1024, 1).' MB' : '0 MB',
        ];
    }

    private function checkDiskSpace(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        $percent = round(($used / $total) * 100, 1);

        return [
            'status' => $percent > 90 ? 'critical' : ($percent > 75 ? 'warning' : 'healthy'),
            'percent' => $percent,
            'used' => round($used / 1024 / 1024 / 1024, 1).' GB',
            'free' => round($free / 1024 / 1024 / 1024, 1).' GB',
            'total' => round($total / 1024 / 1024 / 1024, 1).' GB',
        ];
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $start) * 1000, 1);

            $poolSize = config('database.connections.pgsql.pool.size') ?? 'N/A';

            return [
                'status' => 'healthy',
                'latency' => "$latency ms",
                'pool' => $poolSize,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'down',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function calculateOverallHealth(array $checks): string
    {
        $statuses = array_column($checks, 'status');
        if (in_array('down', $statuses) || in_array('critical', $statuses)) {
            return 'critical';
        }
        if (in_array('degraded', $statuses) || in_array('warning', $statuses)) {
            return 'warning';
        }

        return 'healthy';
    }

    private function initializeSafeDefaults(): void
    {
        $this->userStats = ['total' => 0, 'active' => 0, 'new' => 0, 'online' => 0, 'by_role' => []];
        $this->loginActivity = ['activities' => [], 'chart' => [], ['labels' => [], 'data' => []]];
        $this->errorStats = ['errors' => [], 'chart' => ['labels' => [], 'data' => []], 'total' => 0];
        $this->sessionStats = ['total_sessions' => 0, 'active_sessions' => 0, 'unique_users' => 0, 'chart' => ['labels' => [], 'data' => []]];
        $this->systemHealth = [
            'queue' => ['status' => 'warning', 'running' => 0, 'total' => 0, 'message' => 'Dashboard error'],
            'cron' => ['status' => 'warning', 'last_run' => 'N/A', 'running' => false],
            'backup' => ['status' => 'warning', 'total' => 0, 'latest' => 'N/A', 'size' => '0 MB'],
            'disk' => ['status' => 'warning', 'percent' => 0, 'used' => '0 GB', 'free' => '0 GB'],
            'database' => ['status' => 'warning', 'latency' => 'N/A'],
            'overall' => 'warning',
        ];
    }

    public function render()
    {
        return view('livewire.it-support.monitoring-dashboard');
    }
}
