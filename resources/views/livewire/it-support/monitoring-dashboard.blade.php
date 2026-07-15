<div x-data="{ systemHealth: @entangle('systemHealth'), openIndex: null }">
    <x-page-shell :title="__('Monitoring Dashboard')" :description="__('Real-time system health, user activity & security monitoring')">
        <x-slot name="actions">
            @can('view_system_health')
            <x-button variant="primary" icon="refresh" wire:click="refresh">
                {{ __('Refresh') }}
            </x-button>
            @endcan
        </x-slot>

        {{-- System Health Overview (Cards) --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <div class="flex min-h-[4rem] flex-col justify-center rounded-xl border border-outline-variant/50 bg-canvas p-4 shadow-soft">
                <dt class="text-[0.7rem] font-semibold uppercase tracking-wide text-on-surface-variant">{{ __('Overall') }}</dt>
                <dd class="mt-0.5 text-base font-bold tabular-nums"
                    :class="{
                        'text-success': systemHealth.overall === 'healthy',
                        'text-warning': systemHealth.overall === 'warning',
                        'text-error': systemHealth.overall === 'critical' || systemHealth.overall === 'down'
                    }"
                    x-text="systemHealth.overall?.toUpperCase() || 'N/A'">
                </dd>
            </div>

            @foreach(['queue' => 'Queue', 'cron' => 'Cron', 'backup' => 'Backups', 'disk' => 'Disk', 'database' => 'Database'] as $key => $label)
                <div class="flex min-h-[4rem] flex-col justify-center rounded-xl border border-outline-variant/50 bg-canvas p-4 shadow-soft">
                    <dt class="text-[0.7rem] font-semibold uppercase tracking-wide text-on-surface-variant">{{ $label }}</dt>
                    <dd class="mt-0.5 text-base font-bold tabular-nums"
                        :class="{
                            'text-success': systemHealth['{{$key}}']?.status === 'healthy',
                            'text-warning': systemHealth['{{$key}}']?.status === 'warning',
                            'text-error': systemHealth['{{$key}}']?.status === 'critical' || systemHealth['{{$key}}']?.status === 'down'
                        }"
                        x-text="systemHealth['{{$key}}']?.status?.toUpperCase() || 'N/A'">
                    </dd>
                </div>
            @endforeach
        </div>

        {{-- Summary Section --}}
        <div class="mt-6 rounded-xl border border-outline-variant/50 bg-canvas p-6 shadow-soft">
            <h2 class="text-lg font-semibold tracking-tight text-ink mb-4">{{ __('System Summary') }}</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <p class="text-sm font-medium text-on-surface-variant">{{ __('Total Users') }}</p>
                    <p class="mt-0.5 text-2xl font-bold text-ink">{{ $userStats['total'] }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-on-surface-variant">{{ __('Active Users') }}</p>
                    <p class="mt-0.5 text-2xl font-bold text-ink">{{ $userStats['active'] }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-on-surface-variant">{{ __('Online Now (5min)') }}</p>
                    <p class="mt-0.5 text-2xl font-bold text-success">{{ $userStats['online'] }}</p>
                </div>
            </div>
            <div class="mt-6">
                <button
                    @click="openIndex = openIndex === 1 ? null : 1"
                    class="flex items-center gap-2 text-sm font-medium text-primary transition-smooth hover:text-primary-deep"
                >
                    <span>{{ __('View Details') }}</span>
                    <span class="material-symbols-outlined transition-transform" :class="{ 'rotate-180': openIndex === 1 }">expand_more</span>
                </button>
            </div>
        </div>

        {{-- Collapsible Details --}}
        <div x-show="openIndex === 1" class="mt-4">
            <div class="rounded-xl border border-outline-variant/50 bg-surface-container-low p-4">
                <h3 class="mb-3 text-sm font-medium text-on-surface-variant">{{ __('Live Metrics') }}</h3>
                <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                    <div class="flex justify-between">
                        <span class="text-on-surface-variant">{{ __('Recent Logins:') }}</span>
                        <span class="font-medium text-ink">{{ count($loginActivity['activities']) }} {{ __('events') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-on-surface-variant">{{ __('System Errors:') }}</span>
                        <span class="font-medium {{ $errorStats['total'] > 0 ? 'text-error' : 'text-success' }}">{{ $errorStats['total'] }} {{ __('errors') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-on-surface-variant">{{ __('Active Sessions:') }}</span>
                        <span class="font-medium text-ink">{{ $sessionStats['active_sessions'] }} {{ __('active') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-on-surface-variant">{{ __('Unique Users:') }}</span>
                        <span class="font-medium text-ink">{{ $sessionStats['unique_users'] }} {{ __('users') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </x-page-shell>
</div>