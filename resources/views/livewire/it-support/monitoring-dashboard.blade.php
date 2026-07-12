<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">IT Support Monitoring Dashboard</h1>
                <p class="text-gray-600 mt-1">Real-time system health, user activity & security monitoring</p>
            </div>
            <div class="flex items-center gap-4">
                <select wire:model="timeRange" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="24h">Last 24 Hours</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                </select>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="autoRefresh" class="rounded border-gray-300">
                    Auto-refresh ({{ $refreshInterval }}s)
                </label>
                <button wire:click="refreshData" class="btn-primary" :disabled="$wire.loading">
                    <svg class="animate-spin -ml-1 -mr-2 h-4 w-4" wire:loading wire:target="refreshData" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Refresh
                </button>
            </div>
        </div>

        <!-- System Health Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6 border-l-4" :class="[
                $systemHealth['overall'] === 'healthy' ? 'border-green-500' : ($systemHealth['overall'] === 'warning' ? 'border-yellow-500' : 'border-red-500')
            ]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Overall Status</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ ucfirst($systemHealth['overall']) }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" :class="[
                        $systemHealth['overall'] === 'healthy' ? 'bg-green-100 text-green-600' : ($systemHealth['overall'] === 'warning' ? 'bg-yellow-100 text-yellow-600' : 'bg-red-100 text-red-600')
                    ]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="$systemHealth['overall'] === 'healthy' ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' : ($systemHealth['overall'] === 'warning' ? 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' : 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z')"></path>
                        </svg>
                    </div>
                </div>
            </div>

            @foreach(['queue' => 'Queue Workers', 'cron' => 'Cron Jobs', 'backup' => 'Backups', 'disk' => 'Disk Space', 'database' => 'Database'] as $key => $label)
                <div class="bg-white rounded-lg shadow p-6 border-l-4" :class="[
                    $systemHealth[$key]['status'] === 'healthy' ? 'border-green-500' : ($systemHealth[$key]['status'] === 'warning' ? 'border-yellow-500' : ($systemHealth[$key]['status'] === 'critical' ? 'border-red-500' : 'border-red-500'))
                ]">
                    <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
                    <p class="text-xl font-bold text-gray-900 mt-1" :class="[
                        $systemHealth[$key]['status'] === 'healthy' ? 'text-green-600' : ($systemHealth[$key]['status'] === 'warning' ? 'text-yellow-600' : 'text-red-600')
                    ]">{{ $systemHealth[$key]['message'] ?? $systemHealth[$key]['status'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $systemHealth[$key]['last_run'] ?? $systemHealth[$key]['latency'] ?? $systemHealth[$key]['percent'] ?? '' }}</p>
                </div>
            @endforeach
        </div>

        <!-- Main Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- User Activity Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">User Activity</h2>
                    <span class="text-sm text-gray-500">{{ $userStats['total'] }} total, {{ $userStats['online'] }} online</span>
                </div>
                <div class="h-80">
                    <canvas id="userActivityChart"></canvas>
                </div>
            </div>

            <!-- Login Activity Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Login Activity</h2>
                    <span class="text-sm text-gray-500">{{ count($loginActivity['activities']) }} events</span>
                </div>
                <div class="h-80">
                    <canvas id="loginChart"></canvas>
                </div>
            </div>

            <!-- Error Rate Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Error Rate</h2>
                    <span class="text-sm text-gray-500">{{ $errorStats['total'] }} errors</span>
                </div>
                <div class="h-80">
                    <canvas id="errorChart"></canvas>
                </div>
            </div>

            <!-- Session Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Active Sessions</h2>
                    <span class="text-sm text-gray-500">{{ $sessionStats['active_sessions'] }} active / {{ $sessionStats['unique_users'] }} users</span>
                </div>
                <div class="h-80">
                    <canvas id="sessionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Details Tables Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Recent Login Activity -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Login Activity</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach(array_slice($loginActivity['activities'], 0, 10) as $activity)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $activity['date'] }} {{ $activity['time'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $activity['user'] }} ({{ $activity['email'] }})</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $activity['event'])) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $activity['ip'] }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" :class="$activity['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'">
                                            {{ ucfirst($activity['status']) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Errors -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Errors</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach(array_slice($errorStats['errors'], 0, 10) as $error)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $error['time'] }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" :class="$error['level'] === 'critical' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'">
                                            {{ ucfirst($error['level']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $error['message'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-500">Total Users</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $userStats['total'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-500">Active (with Employee)</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $userStats['active'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-500">New ({{ $timeRange }})</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $userStats['new'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-500">Online Now (5min)</p>
                <p class="text-3xl font-bold text-green-600 mt-1">{{ $userStats['online'] }}</p>
            </div>
        </div>

        <!-- Users by Role -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Users by Role</h3>
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                @foreach($userStats['by_role'] as $role => $count)
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900">{{ $count }}</p>
                        <p class="text-sm text-gray-500 capitalize">{{ str_replace('-', ' ', $role) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Chart.js CDN + Init -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('livewire:load', () => {
            initCharts();
        });

        document.addEventListener('data-refreshed', () => {
            initCharts();
        });

        function initCharts() {
            const colorPalette = {
                primary: '#3b82f6',
                success: '#22c55e',
                warning: '#f59e0b',
                danger: '#ef4444',
                gray: '#9ca3af'
            };

            // User Activity Chart
            const userCtx = document.getElementById('userActivityChart');
            if (userCtx && !userCtx.chart) {
                userCtx.chart = new Chart(userCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active', 'Inactive', 'Online Now'],
                        datasets: [{
                            data: [
                                @js($userStats['active']),
                                @js($userStats['total'] - $userStats['active']),
                                @js($userStats['online'])
                            ],
                            backgroundColor: [colorPalette.success, colorPalette.gray, colorPalette.primary],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }

            // Login Chart
            const loginCtx = document.getElementById('loginChart');
            if (loginCtx && !loginCtx.chart) {
                loginCtx.chart = new Chart(loginCtx, {
                    type: 'line',
                    data: {
                        labels: @js($loginActivity[2]['labels'] ?? []),
                        datasets: [{
                            label: 'Logins',
                            data: @js($loginActivity[2]['data'] ?? []),
                            borderColor: colorPalette.primary,
                            backgroundColor: colorPalette.primary + '20',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            // Error Chart
            const errorCtx = document.getElementById('errorChart');
            if (errorCtx && !errorCtx.chart) {
                errorCtx.chart = new Chart(errorCtx, {
                    type: 'bar',
                    data: {
                        labels: @js($errorStats['chart']['labels'] ?? []),
                        datasets: [{
                            label: 'Errors',
                            data: @js($errorStats['chart']['data'] ?? []),
                            backgroundColor: colorPalette.danger + '80',
                            borderColor: colorPalette.danger,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            // Session Chart
            const sessionCtx = document.getElementById('sessionChart');
            if (sessionCtx && !sessionCtx.chart) {
                sessionCtx.chart = new Chart(sessionCtx, {
                    type: 'area',
                    data: {
                        labels: @js($sessionStats['chart']['labels'] ?? []),
                        datasets: [{
                            label: 'Active Sessions',
                            data: @js($sessionStats['chart']['data'] ?? []),
                            borderColor: colorPalette.success,
                            backgroundColor: colorPalette.success + '20',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }
        }

        // Auto-refresh
        setInterval(() => {
            if (window.Livewire && @js($autoRefresh)) {
                Livewire.emit('refreshData');
            }
        }, @js($refreshInterval * 1000));
    </script>
</div>