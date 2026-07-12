<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

final class UserDashboard extends Component
{
    public string $period = '24h';

    /** @return array<string, mixed> */
    public function getStats(): array
    {
        $now = now();

        // Total users
        $totalUsers = User::count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $trashedUsers = User::onlyTrashed()->count();

        // Active sessions (last 15 min)
        $activeThreshold = $now->subMinutes(15)->timestamp;
        $onlineSessions = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $activeThreshold)
            ->count();

        // Sessions today
        $todayThreshold = $now->startOfDay()->timestamp;
        $sessionsToday = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $todayThreshold)
            ->count();

        // Total sessions (all time)
        $totalSessions = DB::table('sessions')
            ->whereNotNull('user_id')
            ->count();

        // Role distribution
        $roleDistribution = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('count(*) as total'))
            ->groupBy('roles.name')
            ->orderByDesc('total')
            ->get();

        // Users per day registered (last 7 days)
        $usersPerDay = User::selectRaw("DATE(created_at) as date, COUNT(*) as total")
            ->where('created_at', '>=', $now->subDays(6)->startOfDay())
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        // Failed login attempts (from log file — heuristic)
        $logDate = $now->format('Y-m-d');
        $logFile = storage_path("logs/laravel-{$logDate}.log");
        $failedLoginsToday = 0;
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            preg_match_all('/Failed login|Login failed|Invalid credentials/', $content, $matches);
            $failedLoginsToday = count($matches[0] ?? []);
        }

        // Browser breakdown from sessions
        $browsers = DB::table('sessions')
            ->whereNotNull('user_agent')
            ->whereNotNull('user_id')
            ->selectRaw("
                CASE
                    WHEN user_agent LIKE '%Chrome/%' AND user_agent LIKE '%Edg/%' THEN 'Edge'
                    WHEN user_agent LIKE '%Chrome/%' THEN 'Chrome'
                    WHEN user_agent LIKE '%Firefox/%' THEN 'Firefox'
                    WHEN user_agent LIKE '%Safari/%' AND user_agent NOT LIKE '%Chrome/%' THEN 'Safari'
                    ELSE 'Lainnya'
                END as browser
            ")
            ->selectRaw('COUNT(DISTINCT user_id) as users')
            ->groupByRaw(1)
            ->orderByDesc('users')
            ->get();

        // Top IPs
        $topIps = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('ip_address', '!=', '127.0.0.1')
            ->select('ip_address', DB::raw('COUNT(DISTINCT user_id) as users'))
            ->groupBy('ip_address')
            ->orderByDesc('users')
            ->limit(5)
            ->get();

        return [
            'total_users'          => $totalUsers,
            'verified_users'       => $verifiedUsers,
            'trashed_users'        => $trashedUsers,
            'online_sessions'      => $onlineSessions,
            'sessions_today'       => $sessionsToday,
            'total_sessions'       => $totalSessions,
            'role_distribution'    => $roleDistribution,
            'users_per_day'        => $usersPerDay,
            'failed_logins_today'  => $failedLoginsToday,
            'browsers'             => $browsers,
            'top_ips'              => $topIps,
        ];
    }

    public function render()
    {
        return view('livewire.admin.user-dashboard')
            ->layout('layouts::app.sidebar', ['title' => 'Dashboard Pengguna']);
    }
}
