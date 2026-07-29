@php
    $selectedPeriod = \Carbon\Carbon::createFromDate((int) $year, (int) $month, 1)->translatedFormat('F Y');
    $presentTotal = $metrics['present'] ?? 0;
    $lateTotal = $metrics['late'] ?? 0;
    $sickTotal = $metrics['sick'] ?? 0;
    $excusedTotal = $metrics['excused'] ?? 0;
    $alphaTotal = ($metrics['alpha'] ?? 0) + ($metrics['absent'] ?? 0);
    $attendanceMixTotal = max($presentTotal + $lateTotal + $sickTotal + $excusedTotal + $alphaTotal, 1);
    $topRegions = collect($regionDistribution)
        ->countBy(fn($item) => $item['region'] ?? __('Unknown'))
        ->sortDesc()
        ->take(5);
    $divisionLeaders = collect($divisionStats['labels'] ?? [])
        ->values()
        ->map(
            fn($label, $index) => [
                'label' => $label,
                'value' => $divisionStats['data'][$index] ?? 0,
            ],
        )
        ->sortByDesc('value')
        ->take(5)
        ->values();
    $genderBreakdown = collect([
        ['label' => __('Male'), 'value' => $genderDemographics['male'] ?? 0],
        ['label' => __('Female'), 'value' => $genderDemographics['female'] ?? 0],
    ])
        ->filter(fn($item) => $item['value'] > 0)
        ->values();
    $genderTotal = max($genderBreakdown->sum('value'), 1);
    $summaryCards = [
        [
            'label' => __('Total Workforce'),
            'value' => $summary['total_employees'],
            'hint' => __('Active employees in the organization'),
            'tone' => 'primary',
        ],
        [
            'label' => __('Attendance Rate'),
            'value' => $summary['attendance_rate'] . '%',
            'hint' => __('Presence coverage for the selected period'),
            'tone' => 'emerald',
        ],
        [
            'label' => __('Late Occurrence'),
            'value' => $summary['late_rate'] . '%',
            'hint' => __('Share of late arrivals from recorded presence'),
            'tone' => 'amber',
        ],
        [
            'label' => __('Avg Daily Presence'),
            'value' => $summary['avg_daily_attendance'],
            'hint' => __('Average people present per workday'),
            'tone' => 'teal',
        ],
        [
            'label' => __('Est. Basic Payroll'),
            'value' => 'Rp ' . number_format($estimatedPayroll, 0, ',', '.'),
            'hint' => __('Projected from active employee salary data'),
            'tone' => 'slate',
        ],
    ];
    $analyticsPayload = [
        'trend' => $trend,
        'metrics' => $metrics,
        'division' => $divisionStats,
        'late' => $lateBuckets,
        'absent' => $absentStats,
        'regionDistribution' => $regionDistribution,
        'gender' => $genderDemographics,
        'headcount' => $headcountStats,
    ];
@endphp

<x-admin.page-shell :title="__('Analytics Dashboard')" :description="__('Comprehensive overview of workforce performance.')" data-analytics-charts-root x-data="analyticsChartsComponent"
    x-init="boot()" x-on:chart-update.window="updateCharts($event.detail)"
    x-on:hris-update.window="updateHrisCharts($event.detail)">
    <x-slot name="actions">
        <span
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            <x-heroicon-o-banknotes class="h-4 w-4" />
            {{ __('Work Standard') }}: {{ $workHoursPerDay }} {{ __('Hours / Day') }}
        </span>
    </x-slot>

    <x-slot name="toolbar">
        <x-admin.page-tools :title="__('Filter Analytics Period')" :description="__(
            'Use month and year filters to compare attendance performance, workforce mix, and operational risk over time.',
        )"
            grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-slot name="actions">
                <div wire:loading role="status" aria-live="polite" class="flex items-center px-1 text-primary-600">
                    <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span class="sr-only">{{ __('Loading analytics') }}</span>
                </div>

                <form method="GET" action="{{ route('admin.analytics') }}" class="w-full sm:w-[22rem]">
                    <div class="grid grid-cols-[minmax(10rem,1fr)_7rem] gap-2">
                        <x-forms.tom-select
                            id="analytics-month"
                            name="month"
                            :selected="$month"
                            :submit-on-change="true"
                            placeholder="{{ __('Month') }}"
                            :options="collect(range(1, 12))->map(
                                fn($m) => [
                                    'id' => $m,
                                    'name' => \Carbon\Carbon::create()->month($m)->translatedFormat('F'),
                                ],
                            )"
                        />

                        <x-forms.tom-select
                            id="analytics-year"
                            name="year"
                            :selected="$year"
                            :submit-on-change="true"
                            placeholder="{{ __('Year') }}"
                            :options="collect(range(date('Y') - 1, date('Y')))->map(fn($y) => ['id' => $y, 'name' => $y])"
                        />
                    </div>
                </form>
            </x-slot>
        </x-admin.page-tools>
    </x-slot>

    <div class="space-y-6">
        <!-- Finance & HR Banner -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            @foreach ($summaryCards as $card)
                <div class="group relative flex flex-col justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:shadow dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-[0.65rem] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                        {{ $card['label'] }}
                    </p>
                    <p class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                        {{ $card['value'] }}
                    </p>
                </div>
            @endforeach
        </div>

        <!-- Attendance Trend & Mix -->
        <div class="grid gap-4 xl:grid-cols-[minmax(0,2.2fr)_minmax(280px,1fr)] items-stretch">
            <x-admin.insight-panel class="relative flex flex-col overflow-hidden p-5 h-full">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400">
                            <x-heroicon-s-chart-bar class="h-4 w-4" />
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Attendance Trend') }}</h3>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $selectedPeriod }}</span>
                </div>
                <div class="h-[200px] w-full relative z-10">
                    <canvas x-ref="trendChart" class="!h-full !w-full" role="img" aria-label="{{ __('Attendance trend line chart') }}"></canvas>
                </div>
            </x-admin.insight-panel>

            <x-admin.insight-panel class="relative flex flex-col overflow-hidden p-5 h-full">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-600 dark:bg-slate-800/50 dark:text-slate-400">
                            <x-heroicon-s-chart-pie class="h-4 w-4" />
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Attendance Mix') }}</h3>
                    </div>
                    <span class="text-3xl font-black text-slate-900 dark:text-white">{{ $attendanceMixTotal }}</span>
                </div>
                <div class="flex-1 flex flex-col justify-center space-y-3">
                    @foreach ([['label' => __('Present'), 'value' => $presentTotal, 'color' => 'primary', 'bar' => 'bg-primary-500'], ['label' => __('Late'), 'value' => $lateTotal, 'color' => 'amber', 'bar' => 'bg-amber-500'], ['label' => __('Leave'), 'value' => $sickTotal + $excusedTotal, 'color' => 'sky', 'bar' => 'bg-sky-500'], ['label' => __('Alpha'), 'value' => $alphaTotal, 'color' => 'rose', 'bar' => 'bg-rose-500']] as $row)
                        <div class="group">
                            <div class="mb-1.5 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full {{ $row['bar'] }} shadow-[0_0_8px_rgba(0,0,0,0.1)] shadow-{{ $row['color'] }}-500/50 ring-2 ring-white dark:ring-slate-900"></span>
                                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $row['label'] }}</span>
                                </div>
                                <span class="font-bold text-slate-900 dark:text-white group-hover:text-{{ $row['color'] }}-600 transition-colors text-sm">{{ $row['value'] }}</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-100/80 shadow-inner dark:bg-slate-800/80" role="progressbar" aria-valuenow="{{ $row['value'] }}" aria-valuemax="{{ $attendanceMixTotal }}" aria-label="{{ $row['label'] }}">
                                <div class="h-full rounded-full {{ $row['bar'] }} transition-all duration-1000 ease-out" style="width: {{ round(($row['value'] / $attendanceMixTotal) * 100, 1) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin.insight-panel>
        </div>

        <!-- Operations & HR Overview -->
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <!-- Pending Reimbursements -->
            <x-admin.insight-panel class="group relative overflow-hidden p-5 transition-all duration-300 hover:shadow-lg dark:hover:shadow-indigo-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Pending Reimbursements') }}</p>
                        <h4 class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $operationsMetrics['pending_reimbursements'] ?? 0 }}</h4>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 shadow-sm transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3 dark:bg-indigo-900/30 dark:text-indigo-400">
                        <x-heroicon-o-banknotes class="h-6 w-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs font-medium text-indigo-600 dark:text-indigo-400">
                    <span>{{ __('Requires approval') }}</span>
                    <x-heroicon-s-arrow-right class="h-3 w-3 transition-transform duration-300 group-hover:translate-x-1" />
                </div>
            </x-admin.insight-panel>

            <!-- Pending Cash Advances -->
            <x-admin.insight-panel class="group relative overflow-hidden p-5 transition-all duration-300 hover:shadow-lg dark:hover:shadow-emerald-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Pending Cash Advances') }}</p>
                        <h4 class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $operationsMetrics['pending_cash_advances'] ?? 0 }}</h4>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 shadow-sm transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-3 dark:bg-emerald-900/30 dark:text-emerald-400">
                        <x-heroicon-o-wallet class="h-6 w-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                    <span>{{ __('Needs finance review') }}</span>
                    <x-heroicon-s-arrow-right class="h-3 w-3 transition-transform duration-300 group-hover:translate-x-1" />
                </div>
            </x-admin.insight-panel>

            <!-- Pending Document Requests -->
            <x-admin.insight-panel class="group relative overflow-hidden p-5 transition-all duration-300 hover:shadow-lg dark:hover:shadow-amber-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Document Requests') }}</p>
                        <h4 class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $operationsMetrics['pending_document_requests'] ?? 0 }}</h4>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 shadow-sm transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3 dark:bg-amber-900/30 dark:text-amber-400">
                        <x-heroicon-o-document-text class="h-6 w-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs font-medium text-amber-600 dark:text-amber-400">
                    <span>{{ __('Pending issuance') }}</span>
                    <x-heroicon-s-arrow-right class="h-3 w-3 transition-transform duration-300 group-hover:translate-x-1" />
                </div>
            </x-admin.insight-panel>

            <!-- Pending HR Tasks -->
            <x-admin.insight-panel class="group relative overflow-hidden p-5 transition-all duration-300 hover:shadow-lg dark:hover:shadow-rose-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Active HR Tasks') }}</p>
                        <h4 class="mt-2 text-3xl font-black text-slate-900 dark:text-white">{{ $operationsMetrics['pending_hr_tasks'] ?? 0 }}</h4>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-rose-600 shadow-sm transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-3 dark:bg-rose-900/30 dark:text-rose-400">
                        <x-heroicon-o-clipboard-document-check class="h-6 w-6" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs font-medium text-rose-600 dark:text-rose-400">
                    <span>{{ __('Checklist to complete') }}</span>
                    <x-heroicon-s-arrow-right class="h-3 w-3 transition-transform duration-300 group-hover:translate-x-1" />
                </div>
            </x-admin.insight-panel>
        </div>

        <!-- Map & Headcount -->
        <div class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <x-admin.insight-panel class="flex flex-col overflow-hidden p-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ __('Geographical Distribution') }}</h3>
                <div class="min-h-[280px] w-full flex-1">
                    <div id="employeeOriginsMap" x-ref="employeeOriginsMap" wire:ignore class="h-full w-full rounded-xl border border-slate-200 dark:border-slate-800/55 z-0"></div>
                </div>
            </x-admin.insight-panel>

            <div class="grid gap-4">
                <x-admin.insight-panel class="p-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ __('Headcount Distribution') }}</h3>
                    <div class="h-[200px]"><canvas x-ref="headcountChart" role="img" aria-label="{{ __('Headcount distribution chart') }}"></canvas></div>
                </x-admin.insight-panel>
                <x-admin.insight-panel class="p-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ __('Top Performing Divisions') }}</h3>
                    <div class="space-y-2">
                        @forelse ($divisionLeaders as $index => $division)
                            <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/45 border border-slate-100 dark:border-slate-700/35">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700 dark:bg-primary-900/30 dark:text-primary-400">{{ $index + 1 }}</span>
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $division['label'] }}</span>
                                </div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">{{ $division['value'] }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500">{{ __('No data') }}</p>
                        @endforelse
                    </div>
                </x-admin.insight-panel>
            </div>
        </div>

        <!-- Micro Charts -->
        <div class="grid gap-4 grid-cols-2 xl:grid-cols-5">
            <x-admin.insight-panel class="p-4">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-3">{{ __('Division Performance') }}</h3>
                <div class="h-48"><canvas x-ref="divisionChart" role="img" aria-label="{{ __('Division performance chart') }}"></canvas></div>
            </x-admin.insight-panel>
            <x-admin.insight-panel class="p-4">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-3">{{ __('Status Distribution') }}</h3>
                <div class="h-48"><canvas x-ref="statusChart" role="img" aria-label="{{ __('Status distribution chart') }}"></canvas></div>
            </x-admin.insight-panel>
            <x-admin.insight-panel class="p-4">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-3">{{ __('Late Analysis') }}</h3>
                <div class="h-48"><canvas x-ref="lateChart" role="img" aria-label="{{ __('Late analysis chart') }}"></canvas></div>
            </x-admin.insight-panel>
            <x-admin.insight-panel class="p-4">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-3">{{ __('Gender Split') }}</h3>
                <div class="h-48"><canvas x-ref="genderChart" role="img" aria-label="{{ __('Gender split chart') }}"></canvas></div>
            </x-admin.insight-panel>
            <x-admin.insight-panel class="p-4">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-3">{{ __('Absence Reasons') }}</h3>
                <div class="h-48"><canvas x-ref="absentChart" role="img" aria-label="{{ __('Absence reasons chart') }}"></canvas></div>
            </x-admin.insight-panel>
        </div>

        <!-- Wall of Fame -->
        <div class="grid gap-4 md:grid-cols-3">
            <x-admin.insight-panel class="p-5">
                <div class="mb-4 flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/50">
                        <x-heroicon-s-star class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Early Birds') }}</h3>
                </div>
                <div class="space-y-3">
                    @forelse ($topDiligent as $employee)
                        <div class="group flex items-center gap-3 rounded-xl border border-slate-200/50 bg-white/60 p-2.5 shadow-sm backdrop-blur-sm transition-all hover:-translate-y-0.5 hover:bg-white hover:shadow dark:border-slate-700/50 dark:bg-slate-800/40 dark:hover:bg-slate-800/80">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 text-xs uppercase">
                                {{ substr($employee->name, 0, 2) }}
                            </div>
                            <div class="flex flex-1 items-center justify-between">
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate pr-2">{{ $employee->name }}</span>
                                <span class="shrink-0 rounded-full bg-emerald-100/80 px-2 py-0.5 text-[0.7rem] font-bold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 shadow-sm">{{ gmdate('H:i', $employee->avg_check_in) }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">{{ __('No data') }}</p>
                    @endforelse
                </div>
            </x-admin.insight-panel>

            <x-admin.insight-panel class="p-5">
                <div class="mb-4 flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/50">
                        <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Frequent Late') }}</h3>
                </div>
                <div class="space-y-3">
                    @forelse ($topLate as $employee)
                        <div class="group flex items-center gap-3 rounded-xl border border-slate-200/50 bg-white/60 p-2.5 shadow-sm backdrop-blur-sm transition-all hover:-translate-y-0.5 hover:bg-white hover:shadow dark:border-slate-700/50 dark:bg-slate-800/40 dark:hover:bg-slate-800/80">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 font-bold text-amber-700 dark:bg-amber-900/50 dark:text-amber-300 text-xs uppercase">
                                {{ substr($employee->name, 0, 2) }}
                            </div>
                            <div class="flex flex-1 items-center justify-between">
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate pr-2">{{ $employee->name }}</span>
                                <span class="shrink-0 rounded-full bg-amber-100/80 px-2 py-0.5 text-[0.7rem] font-bold text-amber-700 dark:bg-amber-900/50 dark:text-amber-300 shadow-sm">{{ $employee->late_count }}x</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">{{ __('Everyone on time') }}</p>
                    @endforelse
                </div>
            </x-admin.insight-panel>

            <x-admin.insight-panel class="p-5">
                <div class="mb-4 flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100 dark:bg-rose-900/50">
                        <x-heroicon-s-arrow-right-end-on-rectangle class="h-5 w-5 text-rose-600 dark:text-rose-400" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Early Runners') }}</h3>
                </div>
                <div class="space-y-3">
                    @forelse ($topEarlyLeavers as $employee)
                        <div class="group flex items-center gap-3 rounded-xl border border-slate-200/50 bg-white/60 p-2.5 shadow-sm backdrop-blur-sm transition-all hover:-translate-y-0.5 hover:bg-white hover:shadow dark:border-slate-700/50 dark:bg-slate-800/40 dark:hover:bg-slate-800/80">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-100 font-bold text-rose-700 dark:bg-rose-900/50 dark:text-rose-300 text-xs uppercase">
                                {{ substr($employee->name, 0, 2) }}
                            </div>
                            <div class="flex flex-1 items-center justify-between">
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate pr-2">{{ $employee->name }}</span>
                                <span class="shrink-0 rounded-full bg-rose-100/80 px-2 py-0.5 text-[0.7rem] font-bold text-rose-700 dark:bg-rose-900/50 dark:text-rose-300 shadow-sm">{{ $employee->early_leave_count }}x</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">{{ __('Full attendance') }}</p>
                    @endforelse
                </div>
            </x-admin.insight-panel>
        </div>

        <!-- Extended Analytics Row -->
        <div class="grid gap-4 md:grid-cols-2">
            <!-- Top Regions -->
            <x-admin.insight-panel class="p-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ __('Top Employee Regions') }}</h3>
                <div class="space-y-2.5">
                    @forelse ($topRegions as $region => $count)
                        @php $regionPct = round(($count / max($topRegions->sum(), 1)) * 100, 1); @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $region }}</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ $count }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full bg-teal-500" style="width: {{ $regionPct }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">{{ __('No region data') }}</p>
                    @endforelse
                </div>
            </x-admin.insight-panel>

            <!-- Attendance Rate Gauge -->
            <x-admin.insight-panel class="p-4">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ __('Attendance Rate') }}</h3>
                <div class="flex items-center gap-4">
                    <div class="relative shrink-0 drop-shadow-md">
                        <svg viewBox="0 0 120 120" class="w-24 h-24">
                            <defs>
                                <linearGradient id="gaugeGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#34d399" />
                                    <stop offset="100%" stop-color="#059669" />
                                </linearGradient>
                            </defs>
                            <circle cx="60" cy="60" r="52" fill="none" stroke-width="10" class="stroke-slate-100/80 dark:stroke-slate-800/80" />
                            <circle cx="60" cy="60" r="52" fill="none" stroke-width="10" stroke-linecap="round"
                                stroke="url(#gaugeGradient)"
                                stroke-dasharray="{{ 2 * 3.14159 * 52 }}"
                                stroke-dashoffset="{{ 2 * 3.14159 * 52 * (1 - ($summary['attendance_rate'] ?? 0) / 100) }}"
                                transform="rotate(-90 60 60)" 
                                class="transition-all duration-1000 ease-out" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-xl font-black bg-clip-text text-transparent bg-gradient-to-br from-emerald-600 to-teal-500 dark:from-emerald-400 dark:to-teal-300 drop-shadow-sm">{{ $summary['attendance_rate'] ?? 0 }}%</span>
                        </div>
                    </div>
                    <div class="flex-1 space-y-2 text-xs">
                        <div class="flex justify-between rounded-lg bg-emerald-50/80 p-2.5 backdrop-blur-sm dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400">
                            <span class="font-medium">{{ __('Avg Daily') }}</span>
                            <span class="font-bold">{{ $summary['avg_daily_attendance'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between rounded-lg bg-amber-50/80 p-2.5 backdrop-blur-sm dark:bg-amber-900/20 text-amber-700 dark:text-amber-400">
                            <span class="font-medium">{{ __('Late Rate') }}</span>
                            <span class="font-bold">{{ $summary['late_rate'] ?? 0 }}%</span>
                        </div>
                        <div class="flex justify-between rounded-lg bg-slate-50/80 p-2.5 backdrop-blur-sm dark:bg-slate-800/40 text-slate-700 dark:text-slate-300">
                            <span class="font-medium">{{ __('Workforce') }}</span>
                            <span class="font-bold">{{ $summary['total_employees'] }}</span>
                        </div>
                    </div>
                </div>
            </x-admin.insight-panel>
        </div>
    </div>

    @push('scripts')
        <script>
            window.analyticsChartsPayload = @js($analyticsPayload);

            window.initAnalyticsCharts = (initialData) => ({
                data: initialData,
                charts: {},

                translate(key) {
                    const dict = {
                        'present': '{{ __('Present') }}',
                        'late': '{{ __('Late') }}',
                        'sick': '{{ __('Sick') }}',
                        'excused': '{{ __('Excused') }}',
                        'absent': '{{ __('Absent') }}',
                        'alpha': '{{ __('Alpha') }}',
                        'male': '{{ __('Male') }}',
                        'female': '{{ __('Female') }}'
                    };
                    return dict[key.toLowerCase()] || (key.charAt(0).toUpperCase() + key.slice(1));
                },

                chartTheme() {
                    const dark = document.documentElement.classList.contains('dark');

                    return {
                        grid: dark ? 'rgba(148, 163, 184, 0.12)' : 'rgba(226, 232, 240, 0.9)',
                        tick: dark ? 'rgba(203, 213, 225, 0.74)' : 'rgba(71, 85, 105, 0.82)',
                        legend: dark ? 'rgba(226, 232, 240, 0.84)' : 'rgba(51, 65, 85, 0.86)',
                    };
                },

                init() {
                    this.$nextTick(() => {
                        this.renderCharts();
                    });
                    this.registerMapCleanup();
                },

                normalizePayload(payload) {
                    return Array.isArray(payload) ? (payload[0] || {}) : (payload || {});
                },

                updateCharts(newData) {
                    const payload = this.normalizePayload(newData);

                    this.data.trend = payload.trend;
                    this.data.metrics = payload.metrics;
                    this.data.division = payload.divisionStats;
                    this.data.late = payload.lateBuckets;
                    this.data.absent = payload.absentStats;
                    this.data.regionDistribution = payload.regionDistribution;

                    this.$nextTick(() => this.renderCharts());
                },

                updateHrisCharts(newData) {
                    const payload = this.normalizePayload(newData);

                    this.data.gender = payload.genderDemographics;
                    this.data.headcount = payload.headcountStats;
                    this.$nextTick(() => this.renderCharts());
                },

                registerMapCleanup() {
                    if (this.mapCleanupRegistered) return;

                    this.mapCleanupHandler = () => this.destroyEmployeeOriginsMap();
                    document.addEventListener('livewire:navigating', this.mapCleanupHandler);
                    window.addEventListener('pagehide', this.mapCleanupHandler);
                    this.mapCleanupRegistered = true;
                },

                destroyEmployeeOriginsMap() {
                    const mapElement = this.$refs.employeeOriginsMap || document.getElementById('employeeOriginsMap');
                    const state = mapElement?._analyticsLeaflet;

                    if (!state) return;

                    state.resizeObserver?.disconnect();
                    state.markersLayer?.clearLayers();
                    state.map?.remove();
                    delete mapElement._analyticsLeaflet;
                },

                renderCharts() {
                    if (typeof Chart === 'undefined') {
                        if (this.retryCount === undefined) this.retryCount = 0;
                        if (this.retryCount < 50) {
                            this.retryCount++;
                            setTimeout(() => this.renderCharts(), 100);
                        } else {
                            console.error('Chart.js is not available. Analytics charts cannot be rendered.');
                        }
                        return;
                    }

                    const theme = this.chartTheme();

                    Chart.defaults.color = theme.legend;
                    Chart.defaults.borderColor = theme.grid;

                    this.renderTrendChart();
                    this.renderDivisionChart();
                    this.renderStatusChart();
                    this.renderLateChart();
                    this.renderGenderChart();
                    this.renderHeadcountChart();
                    this.renderEmployeeOriginsMap();
                    this.renderAbsentChart();
                },

                renderTrendChart() {
                    const ctx = this.$refs.trendChart;
                    if (!ctx) return;
                    const theme = this.chartTheme();

                    if (Chart.getChart(ctx)) {
                        Chart.getChart(ctx).destroy();
                    }

                    const presentGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 320);
                    presentGradient.addColorStop(0, 'rgba(22, 163, 74, 0.2)');
                    presentGradient.addColorStop(1, 'rgba(22, 163, 74, 0)');

                    this.charts.trend = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: this.data.trend.labels || [],
                            datasets: [{
                                    label: this.translate('present'),
                                    data: this.data.trend.present || [],
                                    borderColor: '#16a34a',
                                    backgroundColor: presentGradient,
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 2
                                },
                                {
                                    label: this.translate('late'),
                                    data: this.data.trend.late || [],
                                    borderColor: '#f59e0b',
                                    backgroundColor: 'transparent',
                                    tension: 0.35,
                                    pointRadius: 2
                                },
                                {
                                    label: this.translate('absent'),
                                    data: this.data.trend.absent || [],
                                    borderColor: '#ef4444',
                                    backgroundColor: 'transparent',
                                    borderDash: [6, 6],
                                    tension: 0.35,
                                    pointRadius: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 6,
                                    left: 0,
                                    right: 0,
                                    bottom: 0
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    align: 'end',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 8,
                                        color: theme.legend
                                    }
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: theme.tick
                                    },
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    border: {
                                        display: false
                                    },
                                    ticks: {
                                        color: theme.tick
                                    },
                                    grid: {
                                        color: theme.grid,
                                        drawTicks: false
                                    }
                                }
                            }
                        }
                    });
                },

                renderDivisionChart() {
                    const ctx = this.$refs.divisionChart;
                    if (!ctx) return;
                    const theme = this.chartTheme();

                    if (Chart.getChart(ctx)) {
                        Chart.getChart(ctx).destroy();
                    }

                    this.charts.division = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: this.data.division.labels || [],
                            datasets: [{
                                label: '{{ __('Present') }}',
                                data: this.data.division.data || [],
                                backgroundColor: '#16a34a',
                                borderRadius: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: 0
                            },
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: theme.tick
                                    },
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    border: {
                                        display: false
                                    },
                                    ticks: {
                                        color: theme.tick
                                    },
                                    grid: {
                                        color: theme.grid,
                                        drawTicks: false
                                    }
                                }
                            }
                        }
                    });
                },

                renderStatusChart() {
                    const ctx = this.$refs.statusChart;
                    if (!ctx) return;
                    const theme = this.chartTheme();

                    if (Chart.getChart(ctx)) {
                        Chart.getChart(ctx).destroy();
                    }

                    const labels = Object.keys(this.data.metrics || {});
                    const data = Object.values(this.data.metrics || {});

                    this.charts.status = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels.map(l => this.translate(l)),
                            datasets: [{
                                data: data,
                                backgroundColor: ['#16a34a', '#f59e0b', '#0ea5e9', '#8b5cf6', '#ef4444',
                                    '#64748b'
                                ],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '62%',
                            layout: {
                                padding: 0
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    align: 'start',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 8,
                                        padding: 14,
                                        color: theme.legend
                                    }
                                }
                            }
                        }
                    });
                },

                renderLateChart() {
                    const ctx = this.$refs.lateChart;
                    if (!ctx) return;
                    const theme = this.chartTheme();

                    if (Chart.getChart(ctx)) {
                        Chart.getChart(ctx).destroy();
                    }

                    const labels = Object.keys(this.data.late || {});
                    const data = Object.values(this.data.late || {});

                    this.charts.late = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: ['#fde68a', '#fbbf24', '#f59e0b', '#d97706'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: 0
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    align: 'start',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 8,
                                        padding: 14,
                                        color: theme.legend
                                    }
                                }
                            }
                        }
                    });
                },

                renderGenderChart() {
                    const ctx = this.$refs.genderChart;
                    if (!ctx) return;
                    const theme = this.chartTheme();

                    if (Chart.getChart(ctx)) {
                        Chart.getChart(ctx).destroy();
                    }

                    const labels = Object.keys(this.data.gender || {});
                    const data = Object.values(this.data.gender || {});

                    this.charts.gender = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels.map(l => this.translate(l)),
                            datasets: [{
                                data: data,
                                backgroundColor: ['#0f766e', '#16a34a', '#94a3b8'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '62%',
                            layout: {
                                padding: 0
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    align: 'start',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 8,
                                        padding: 14,
                                        color: theme.legend
                                    }
                                }
                            }
                        }
                    });
                },

                renderAbsentChart() {
                    const ctx = this.$refs.absentChart;
                    if (!ctx) return;
                    const theme = this.chartTheme();

                    if (Chart.getChart(ctx)) {
                        Chart.getChart(ctx).destroy();
                    }

                    const labels = Object.keys(this.data.absent || {});
                    const data = Object.values(this.data.absent || {});

                    if (!labels.length) {
                        return;
                    }

                    this.charts.absent = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels.map(l => this.translate(l)),
                            datasets: [{
                                data: data,
                                backgroundColor: ['#0ea5e9', '#8b5cf6', '#e11d48', '#f59e0b'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '62%',
                            layout: {
                                padding: 0
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    align: 'start',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 8,
                                        padding: 14,
                                        color: theme.legend
                                    }
                                }
                            }
                        }
                    });
                },

                renderHeadcountChart() {
                    const ctx = this.$refs.headcountChart;
                    if (!ctx) return;
                    const theme = this.chartTheme();

                    if (Chart.getChart(ctx)) {
                        Chart.getChart(ctx).destroy();
                    }

                    this.charts.headcount = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: this.data.headcount?.labels || [],
                            datasets: [{
                                label: '{{ __('Headcount') }}',
                                data: this.data.headcount?.data || [],
                                backgroundColor: '#0f766e',
                                borderRadius: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: 0
                            },
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: theme.tick
                                    },
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    border: {
                                        display: false
                                    },
                                    ticks: {
                                        color: theme.tick
                                    },
                                    grid: {
                                        color: theme.grid,
                                        drawTicks: false
                                    }
                                }
                            }
                        }
                    });
                },

                renderEmployeeOriginsMap() {
                    if (typeof L === 'undefined' || typeof L.markerClusterGroup === 'undefined') {
                        setTimeout(() => this.renderEmployeeOriginsMap(), 100);
                        return;
                    }

                    const mapElement = this.$refs.employeeOriginsMap || document.getElementById('employeeOriginsMap');
                    if (!mapElement) return;

                    if (!mapElement._analyticsLeaflet) {
                        const map = L.map(mapElement, {
                            fadeAnimation: false,
                            markerZoomAnimation: false,
                            wheelDebounceTime: 80,
                            zoomAnimation: false,
                        }).setView([-2.548926, 118.0148634], 5);

                        const isDark = document.documentElement.classList.contains('dark');
                        const tileUrl = isDark 
                            ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
                            : 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';

                        L.tileLayer(tileUrl, {
                            attribution: '&copy; <a href="https://carto.com/attributions">CARTO</a>',
                            subdomains: 'abcd',
                            maxZoom: 20
                        }).addTo(map);

                        const markersLayer = L.markerClusterGroup({
                            animate: false,
                            animateAddingMarkers: false,
                            showCoverageOnHover: false,
                            spiderfyOnMaxZoom: true,
                            maxClusterRadius: 50,
                            iconCreateFunction: function(cluster) {
                                const markers = cluster.getAllChildMarkers();
                                return new L.DivIcon({
                                    html: `<div class="bg-primary-600 text-white font-bold rounded-full w-full h-full flex items-center justify-center border-[3px] border-white dark:border-slate-800 shadow-md"><span>${markers.length}</span></div>`,
                                    className: 'custom-clean-cluster bg-transparent',
                                    iconSize: new L.Point(38, 38)
                                });
                            }
                        }).addTo(map);

                        mapElement._analyticsLeaflet = {
                            map,
                            markersLayer,
                            resizeObserver: null,
                        };

                        if (typeof ResizeObserver !== 'undefined') {
                            mapElement._analyticsLeaflet.resizeObserver = new ResizeObserver(() => {
                                if (mapElement._analyticsLeaflet?.map) {
                                    mapElement._analyticsLeaflet.map.invalidateSize();
                                }
                            });
                            mapElement._analyticsLeaflet.resizeObserver.observe(mapElement);
                        }
                    }

                    const {
                        map,
                        markersLayer
                    } = mapElement._analyticsLeaflet;
                    markersLayer.clearLayers();

                    const mapData = this.data.regionDistribution || [];
                    if (mapData.length > 0) {
                        const bounds = L.latLngBounds();

                        mapData.forEach(item => {
                            if (item.lat && item.lng) {
                                const latLng = [item.lat, item.lng];
                                bounds.extend(latLng);

                                const customIcon = L.divIcon({
                                    className: 'custom-div-icon',
                                    html: `
                                        <div class="relative flex items-center justify-center rounded-full border-[3px] border-white dark:border-slate-800 shadow-sm text-white bg-primary-500 w-8 h-8">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        </div>
                                    `,
                                    iconSize: [32, 32],
                                    iconAnchor: [16, 16]
                                });

                                const marker = L.marker(latLng, {
                                    icon: customIcon
                                });
                                const popupContent = `
                                    <div class="p-2 min-w-[120px] text-center">
                                        <div class="font-bold text-sm text-gray-800 mb-1">${item.name}</div>
                                        <div class="text-xs text-gray-500">${item.region}</div>
                                    </div>
                                `;
                                marker.bindPopup(popupContent);
                                markersLayer.addLayer(marker);
                            }
                        });

                        if (bounds.isValid()) {
                            map.fitBounds(bounds, {
                                padding: [40, 40],
                                maxZoom: 8
                            });
                        }
                    } else {
                        map.setView([-2.548926, 118.0148634], 5);
                    }

                    setTimeout(() => {
                        map.invalidateSize();
                    }, 200);
                }
            });

            window.registerAnalyticsChartsComponent = window.registerAnalyticsChartsComponent || function() {
                if (!window.Alpine) {
                    return false;
                }

                if (!window.__analyticsChartsComponentRegistered) {
                    Alpine.data('analyticsChartsComponent', () => {
                        const base = window.initAnalyticsCharts(window.analyticsChartsPayload || {});

                        return {
                            ...base,
                            boot() {
                                this.init();
                            },
                        };
                    });

                    window.__analyticsChartsComponentRegistered = true;
                }

                return true;
            };

            window.initAnalyticsChartsPage = function() {
                if (!window.registerAnalyticsChartsComponent || !window.registerAnalyticsChartsComponent()) {
                    return;
                }

                document.querySelectorAll('[data-analytics-charts-root]').forEach((root) => {
                    if (root._x_dataStack || root.__x) {
                        const component = window.Alpine?.$data?.(root);

                        if (component?.data) {
                            component.data = window.analyticsChartsPayload || {};
                            component.renderCharts?.();
                        }

                        return;
                    }

                    window.Alpine.initTree(root);
                });
            };

            if (!window.registerAnalyticsChartsComponent()) {
                document.addEventListener('alpine:init', () => {
                    window.initAnalyticsChartsPage();
                }, {
                    once: true
                });
            } else {
                queueMicrotask(() => {
                    window.initAnalyticsChartsPage();
                });
            }

            if (!window.__analyticsChartsNavigatedListenerRegistered) {
                document.addEventListener('livewire:navigated', () => {
                    if (document.querySelector('[data-analytics-charts-root]')) {
                        queueMicrotask(() => {
                            window.initAnalyticsChartsPage();
                        });
                    }
                });

                window.__analyticsChartsNavigatedListenerRegistered = true;
            }
        </script>
    @endpush
</x-admin.page-shell>
