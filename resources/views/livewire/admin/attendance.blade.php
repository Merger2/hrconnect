@php
    use Illuminate\Support\Carbon;
    $isPerDayFilter = $startDate === $endDate;
    $showUserDetail = true;

    // Export Logic
    $isLocked = false;
    $exportUrl = route('admin.attendances.report', [
        'startDate' => $startDate,
        'endDate' => $endDate,
        'division' => $division,
        'jobTitle' => $jobTitle,
    ]);
    $excelUrl = route('admin.attendances.report', [
        'startDate' => $startDate,
        'endDate' => $endDate,
        'division' => $division,
        'jobTitle' => $jobTitle,
        'format' => 'excel',
    ]);
    $lockAction =
        "\$dispatch('feature-lock', { title: " .
        json_encode(__('Export Locked')) .
        ', message: ' .
        json_encode(__('This feature is available in the Enterprise Edition. Please upgrade.')) .
        ' })';
    $attendanceStatusMeta = static function (?string $status): array {
        $enum = $status ? \App\Enums\AttendanceStatus::tryFrom($status) : null;

        if (! $enum) {
            return [
                'label' => '-',
                'dot' => 'bg-slate-400',
                'color' => 'bg-slate-50 text-slate-600 ring-slate-500/10',
                'cell' => 'bg-slate-50 text-slate-600 ring-slate-500/10',
                'short' => '-',
            ];
        }

        return [
            'label' => __($enum->label()),
            'dot' => $enum->dot(),
            'color' => $enum->badge(),
            'cell' => $enum->cell(),
            'short' => $enum->short(),
        ];
    };
    $attendanceRiskMeta = static function (?string $level, int $score = 0): array {
        return match ($level) {
            'high' => [
                'label' => __('High risk'),
                'color' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                'short' => 'R'.$score,
            ],
            'medium' => [
                'label' => __('Medium risk'),
                'color' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                'short' => 'R'.$score,
            ],
            default => [
                'label' => __('Low risk'),
                'color' => 'bg-slate-50 text-slate-600 ring-slate-500/10',
                'short' => 'R'.$score,
            ],
        };
    };
@endphp
<x-admin.page-shell :title="__('Attendance Data')" :description="__('Monitor employee attendance, shifts, and status.')">
    <x-slot name="actions">
        @if ($isLocked)
            <x-actions.button type="button" variant="secondary" x-on:click.prevent="{{ $lockAction }}"
                class="w-full sm:w-auto">
                <x-heroicon-o-printer class="h-5 w-5" />
                {{ __('Export Report') }}
                <x-heroicon-o-lock-closed class="h-4 w-4" />
            </x-actions.button>
        @else
            <div x-data="{
                start: @entangle('startDate'),
                end: @entangle('endDate'),
                get showWarning() {
                    if (!this.start || !this.end) return false;
                    const start = new Date(this.start);
                    const end = new Date(this.end);
                    const diffTime = Math.abs(end - start);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    return diffDays > 31;
                }
            }" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <div x-show="showWarning" x-transition
                    class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">
                    <x-heroicon-m-exclamation-triangle class="h-4 w-4" />
                    {{ __('Range > 1 Month: Excel Recommended') }}
                </div>

                <x-navigation.dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <x-actions.button type="button" variant="secondary" class="w-full sm:w-auto">
                            <x-heroicon-o-printer class="h-5 w-5" />
                            {{ __('Export Report') }}
                            <svg class="ms-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </x-actions.button>
                    </x-slot>

                    <x-slot name="content">
                        <x-navigation.dropdown-link href="{{ $exportUrl }}">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-document-text class="h-4 w-4" /> {{ __('Export as PDF') }}
                            </div>
                        </x-navigation.dropdown-link>
                        <x-navigation.dropdown-link href="{{ $excelUrl }}">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-table-cells class="h-4 w-4" /> {{ __('Export as Excel') }}
                            </div>
                        </x-navigation.dropdown-link>
                    </x-slot>
                </x-navigation.dropdown>
            </div>
        @endif
    </x-slot>

    <x-slot name="toolbar">
        <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="col-span-1">
                <x-forms.label for="start_date" value="{{ __('Start Date') }}" class="mb-1.5 block" />
                <div wire:ignore>
                    <x-forms.input type="date" id="start_date" wire:model.live="startDate"
                        value="{{ $startDate }}" class="w-full" />
                </div>
            </div>

            <div class="col-span-1">
                <x-forms.label for="end_date" value="{{ __('End Date') }}" class="mb-1.5 block" />
                <div wire:ignore>
                    <x-forms.input type="date" id="end_date" wire:model.live="endDate" value="{{ $endDate }}"
                        class="w-full" />
                </div>
            </div>

            <div class="col-span-1">
                <x-forms.label for="filter_division" value="{{ __('Division') }}" class="mb-1.5 block" />
                <x-forms.tom-select id="filter_division" wire:model.live="division" placeholder="{{ __('All') }}"
                    :options="\App\Models\Division::all()->map(fn($d) => ['id' => $d->id, 'name' => $d->name])" />
            </div>

            <div class="col-span-1">
                <x-forms.label for="filter_jobTitle" value="{{ __('Job Title') }}" class="mb-1.5 block" />
                <x-forms.tom-select id="filter_jobTitle" wire:model.live="jobTitle" placeholder="{{ __('All') }}"
                    :options="\App\Models\JobTitle::all()->map(fn($j) => ['id' => $j->id, 'name' => $j->name])" />
            </div>

            <div class="col-span-1">
                <x-forms.label for="attendance-risk-filter" value="{{ __('Risk') }}" class="mb-1.5 block" />
                <x-forms.select id="attendance-risk-filter" wire:model.live="riskFilter">
                    <option value="all">{{ __('All risk') }}</option>
                    <option value="medium_high">{{ __('Medium + high') }}</option>
                    <option value="high">{{ __('High only') }}</option>
                    <option value="suspicious">{{ __('Flagged') }}</option>
                </x-forms.select>
            </div>

            <div class="col-span-1">
                <x-forms.label for="attendance-search" value="{{ __('Search employee') }}" class="mb-1.5 block" />
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400" />
                    </div>
                    <x-forms.input id="attendance-search" type="text" wire:model.live.debounce.500ms="search"
                        placeholder="{{ __('Search name or NIP...') }}"
                        class="block w-full border-0 py-2.5 pl-10 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6" />
                </div>
            </div>
        </x-admin.page-tools>
    </x-slot>

    <div wire:poll.5s class="mb-6">
        <x-admin.import-export-run-list :runs="$recentReportRuns" :title="__('Attendance report export jobs')" :description="__('PDF and Excel attendance reports run in the background. Download completed files here.')" :empty="__('No attendance report export jobs yet.')" />
    </div>

    <!-- Content -->
    <x-admin.panel>
        <!-- Desktop Table -->
        <div class="hidden lg:block lg:overflow-x-auto">
            <table class="w-full whitespace-nowrap text-left text-sm">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('Employee') }}</th>
                        @if ($showUserDetail)
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('NIP') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Division') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Job Title') }}</th>
                            @if ($isPerDayFilter)
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Shift') }}</th>
                            @endif
                        @endif

                        @foreach ($dates as $date)
                            @php
                                $textClass =
                                    !$isPerDayFilter && ($date->isSunday() || $date->isFriday())
                                        ? ($date->isSunday()
                                            ? 'text-red-500 font-bold'
                                            : 'text-green-600 font-bold')
                                        : 'text-gray-900';
                            @endphp
                            <th scope="col"
                                class="px-2 py-4 text-center font-medium border-l border-gray-100 {{ $textClass }}">
                                @if ($isPerDayFilter)
                                    {{ __('Status') }}
                                @else
                                    {{ $date->format('d/m') }}
                                @endif
                            </th>
                        @endforeach

                        @if ($isPerDayFilter)
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Time In') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Time Out') }}</th>
                        @endif

                        @if (!$isPerDayFilter)
                            @foreach (['H', 'T', 'I', 'S', 'A'] as $_st)
                                <th scope="col"
                                    class="px-2 py-4 text-center font-medium border-l border-gray-100">
                                    {{ __($_st) }}</th>
                            @endforeach
                        @endif

                        @if ($isPerDayFilter)
                            <th scope="col" class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($employees as $employee)
                        @php $attendances = $employee->attendances; @endphp
                        <tr wire:key="{{ $employee->id }}"
                            class="group hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $employee->name }}
                            </td>
                            @if ($showUserDetail)
                                <td class="px-4 py-3 text-gray-500">{{ $employee->nip }}</td>
                                <td class="px-4 py-3 text-gray-500">
                                    {{ $employee->division?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-500">
                                    {{ $employee->jobTitle?->name ?? '-' }}</td>
                                @if ($isPerDayFilter)
                                    @php
                                        $attendance = $employee->attendances->first();
                                    @endphp
                                    <td class="px-4 py-3 text-gray-900">
                                        {{ $attendance['shift'] ?? '-' }}</td>
                                @endif
                            @endif

                            @php
                                $presentCount = 0;
                                $lateCount = 0;
                                $excusedCount = 0;
                                $sickCount = 0;
                                $absentCount = 0;
                            @endphp

                            @foreach ($dates as $date)
                                @php
                                    $attendance = $attendances->firstWhere(
                                        fn($v) => \Carbon\Carbon::parse($v['date'])->isSameDay($date),
                                    );
                                    $status = ($attendance ?? [
                                        'status' => $date->isWeekend() || !$date->isPast() ? '-' : 'absent',
                                    ])['status'];

                                    $statusMeta = $attendanceStatusMeta($status);
                                    $riskScore = (int) ($attendance['risk_score'] ?? 0);
                                    $riskLevel = $attendance['risk_level'] ?? null;
                                    $riskMeta = $attendanceRiskMeta($riskLevel, $riskScore);
                                    $cellClass = $statusMeta['cell'];

                                    // Count stats
                                    switch ($status) {
                                        case 'present':
                                            $presentCount++;
                                            break;
                                        case 'late':
                                            $lateCount++;
                                            break;
                                        case 'excused':
                                            $excusedCount++;
                                            break;
                                        case 'sick':
                                            $sickCount++;
                                            break;
                                        case 'absent':
                                            $absentCount++;
                                            break;
                                    }
                                @endphp

                                <td class="px-2 py-4 text-center border-l border-gray-100">
                                    @if ($attendance && ($attendance['attachment'] || $attendance['coordinates']))
                                        <button type="button" wire:click="show({{ $attendance['id'] }})"
                                            aria-label="{{ __('View attendance details') }}: {{ $employee->name }}, {{ $date->format('Y-m-d') }}"
                                            class="{{ $isPerDayFilter ? 'inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-1 text-xs' : 'wcag-touch-target h-full w-full rounded' }} {{ $isPerDayFilter ? $statusMeta['color'] : $cellClass }} font-medium ring-1 ring-inset transition-all hover:ring-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                            @if ($isPerDayFilter)
                                                <span class="h-1.5 w-1.5 rounded-full {{ $statusMeta['dot'] }}"></span>
                                                {{ $statusMeta['label'] }}
                                                @if ($attendance['is_suspicious'] ?? false)
                                                    <span title="{{ $attendance['suspicious_reason'] }}"
                                                        class="ml-1 cursor-help text-rose-500">!</span>
                                                @endif
                                                @if ($riskScore > 0)
                                                    <span title="{{ $riskMeta['label'] }}"
                                                        class="ml-1 rounded-full px-1.5 py-0.5 text-[10px] font-bold ring-1 ring-inset {{ $riskMeta['color'] }}">
                                                        {{ $riskMeta['short'] }}
                                                    </span>
                                                @endif
                                            @else
                                                {{ $statusMeta['short'] }}
                                                @if ($riskScore > 0)
                                                    <span class="sr-only">{{ $riskMeta['label'] }} {{ $riskScore }}</span>
                                                @endif
                                            @endif
                                        </button>
                                    @else
                                        <span
                                            class="{{ $isPerDayFilter ? 'inline-flex items-center justify-center gap-1 rounded-full px-2.5 py-1 text-xs' : 'inline-block w-full rounded' }} {{ $isPerDayFilter ? $statusMeta['color'] : $cellClass }} font-medium ring-1 ring-inset">
                                            @if ($isPerDayFilter)
                                                <span class="h-1.5 w-1.5 rounded-full {{ $statusMeta['dot'] }}"></span>
                                                {{ $statusMeta['label'] }}
                                                @if ($riskScore > 0)
                                                    <span title="{{ $riskMeta['label'] }}"
                                                        class="ml-1 rounded-full px-1.5 py-0.5 text-[10px] font-bold ring-1 ring-inset {{ $riskMeta['color'] }}">
                                                        {{ $riskMeta['short'] }}
                                                    </span>
                                                @endif
                                            @else
                                                {{ $statusMeta['short'] }}
                                            @endif
                                        </span>
                                    @endif
                                </td>
                            @endforeach

                            @if ($isPerDayFilter)
                                @php
                                    $dailyAttendance = $employee->attendances->first();
                                    $timeIn = $dailyAttendance ? format_time($dailyAttendance->time_in) : null;
                                    $timeOut = $dailyAttendance ? format_time($dailyAttendance->time_out) : null;
                                @endphp
                                <td class="px-4 py-3 text-gray-900">{{ $timeIn ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-900">{{ $timeOut ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($attendance && ($attendance['attachment'] || $attendance['coordinates']))
                                        <div class="flex justify-end">
                                            <x-actions.icon-button wire:click="show({{ $attendance['id'] }})"
                                                variant="primary"
                                                label="{{ __('View attendance details') }}: {{ $employee->name }}">
                                                <x-heroicon-m-eye class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            @endif

                            @if (!$isPerDayFilter)
                                @foreach ([$presentCount, $lateCount, $excusedCount, $sickCount, $absentCount] as $count)
                                    <td
                                        class="px-2 py-4 text-center border-l border-gray-100 font-medium text-gray-700">
                                        {{ $count }}
                                    </td>
                                @endforeach
                            @endif

                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($dates) + ($isPerDayFilter ? 8 : 10) }}"
                                class="px-4 py-6 text-center text-gray-500">
                                <x-admin.empty-state :title="__('No attendance records found')"
                                    class="border-0 bg-transparent p-0 shadow-none">
                                    <x-slot name="icon">
                                        <x-heroicon-o-calendar class="h-12 w-12 text-gray-300" />
                                    </x-slot>
                                </x-admin.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View (Optimized) -->
        <div class="grid grid-cols-1 lg:hidden divide-y divide-gray-200">
            @foreach ($employees as $employee)
                <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h4 class="font-bold text-gray-900">{{ $employee->name }}</h4>
                            <p class="text-xs text-gray-500">{{ $employee->division?->name }} •
                                {{ $employee->jobTitle?->name }}</p>
                        </div>
                        @if ($isPerDayFilter)
                            @php
                                $att = $employee->attendances->first();
                                $status =
                                    $att['status'] ??
                                    ($startDate == $endDate &&
                                    \Carbon\Carbon::parse($startDate)->isPast() &&
                                    !\Carbon\Carbon::parse($startDate)->isWeekend()
                                        ? 'absent'
                                        : '-');
                                $statusMeta = $attendanceStatusMeta($status);
                                $riskScore = (int) ($att['risk_score'] ?? 0);
                                $riskMeta = $attendanceRiskMeta($att['risk_level'] ?? null, $riskScore);
                            @endphp
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusMeta['color'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $statusMeta['dot'] }}"></span>
                                {{ $statusMeta['label'] }}
                                @if ($att && ($att['is_suspicious'] ?? false))
                                    <span title="{{ $att['suspicious_reason'] }}"
                                        class="ml-1 cursor-help text-rose-500">!</span>
                                @endif
                                @if ($riskScore > 0)
                                    <span title="{{ $riskMeta['label'] }}"
                                        class="rounded-full px-1.5 py-0.5 text-[10px] font-bold ring-1 ring-inset {{ $riskMeta['color'] }}">
                                        {{ $riskMeta['short'] }}
                                    </span>
                                @endif
                            </span>
                        @endif
                    </div>

                    <!-- Mini Stats for Range View -->
                    @if (!$isPerDayFilter)
                        @php
                            $attendances = $employee->attendances;
                            $p = 0;
                            $l = 0;
                            $a = 0;
                            foreach ($dates as $d) {
                                $s = ($attendances->firstWhere(
                                    fn($v) => \Carbon\Carbon::parse($v['date'])->isSameDay($d),
                                ) ?? ['status' => 'absent'])['status'];
                                if ($s == 'present') {
                                    $p++;
                                } elseif ($s == 'late') {
                                    $l++;
                                } elseif ($s == 'absent') {
                                    $a++;
                                }
                            }
                        @endphp
                        <div class="grid grid-cols-3 gap-2 mt-3 text-center">
                            <div class="bg-green-50 p-1.5 rounded text-xs">
                                <span class="block font-bold text-green-700">{{ $p }}</span>
                                <span class="text-green-600">{{ __('Present') }}</span>
                            </div>
                            <div class="bg-amber-50 p-1.5 rounded text-xs">
                                <span class="block font-bold text-amber-700">{{ $l }}</span>
                                <span class="text-amber-600">{{ __('Late') }}</span>
                            </div>
                            <div class="bg-red-50 p-1.5 rounded text-xs">
                                <span class="block font-bold text-red-700">{{ $a }}</span>
                                <span class="text-red-600">{{ __('Absent') }}</span>
                            </div>
                        </div>
                    @else
                        <!-- Detail for Single Day -->
                        <div class="grid grid-cols-2 gap-4 mt-3 text-sm">
                            <div>
                                <span class="text-gray-500 text-xs block">{{ __('Time In') }}</span>
                                <span
                                    class="font-mono text-gray-900">{{ $att['time_in'] ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 text-xs block">{{ __('Time Out') }}</span>
                                <span
                                    class="font-mono text-gray-900">{{ $att['time_out'] ?? '-' }}</span>
                            </div>
                        </div>
                        @if ($att && ($att['attachment'] || $att['coordinates']))
                            <x-actions.button type="button" wire:click="show({{ $att['id'] }})"
                                variant="secondary" size="sm"
                                label="{{ __('View attendance details') }}: {{ $employee->name }}"
                                class="mt-3 w-full">
                                {{ __('View Details') }}
                            </x-actions.button>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>

        @if ($employees->hasPages())
            <div class="border-t border-gray-200 bg-gray-50 px-4 py-2.5">
                {{ $employees->links() }}
            </div>
        @endif
    </x-admin.panel>

    <x-shared.attendance-detail-modal :current-attendance="$currentAttendance" />
    @stack('attendance-detail-scripts')
</x-admin.page-shell>
