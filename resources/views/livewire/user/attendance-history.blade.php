@php
    $totalTracked = collect(['present', 'late', 'excused', 'sick'])->sum(fn ($key) => $counts[$key] ?? 0);
    $attendanceRate = $workingDaysCount > 0 ? min(100, round(($totalTracked / $workingDaysCount) * 100)) : 0;
    $monthOptions = collect(range(1, 12))->map(fn ($month) => [
        'id' => sprintf('%02d', $month),
        'name' => Carbon\Carbon::create()->month($month)->translatedFormat('F'),
    ])->values()->toArray();
    $yearOptions = collect(range(date('Y') - 5, date('Y') + 1))->map(fn ($year) => [
        'id' => $year,
        'name' => $year,
    ])->values()->toArray();
    $statusStyles = [
        'present' => ['dot' => 'bg-emerald-500', 'ring' => 'ring-emerald-400/30', 'label' => __('Present')],
        'late' => ['dot' => 'bg-amber-500', 'ring' => 'ring-amber-400/30', 'label' => __('Late')],
        'excused' => ['dot' => 'bg-sky-500', 'ring' => 'ring-sky-400/30', 'label' => __('Excused')],
        'sick' => ['dot' => 'bg-purple-500', 'ring' => 'ring-purple-400/30', 'label' => __('Sick')],
        'permission' => ['dot' => 'bg-teal-500', 'ring' => 'ring-teal-400/30', 'label' => __('Permission')],
        'leave' => ['dot' => 'bg-indigo-500', 'ring' => 'ring-indigo-400/30', 'label' => __('Leave')],
        'absent' => ['dot' => 'bg-rose-500', 'ring' => 'ring-rose-400/30', 'label' => __('Absent')],
        'rejected' => ['dot' => 'bg-rose-500', 'ring' => 'ring-rose-400/30', 'label' => __('Rejected')],
    ];
    $summaryCards = [
        ['label' => __('Present'), 'key' => 'present', 'color' => 'text-emerald-600 dark:text-emerald-300', 'bg' => 'bg-emerald-500'],
        ['label' => __('Late'), 'key' => 'late', 'color' => 'text-amber-600 dark:text-amber-300', 'bg' => 'bg-amber-500'],
        ['label' => __('Excused'), 'key' => 'excused', 'color' => 'text-sky-600 dark:text-sky-300', 'bg' => 'bg-sky-500'],
        ['label' => __('Sick'), 'key' => 'sick', 'color' => 'text-purple-600 dark:text-purple-300', 'bg' => 'bg-purple-500'],
        ['label' => __('Absent'), 'key' => 'absent', 'color' => 'text-rose-600 dark:text-rose-300', 'bg' => 'bg-rose-500'],
    ];
@endphp

<div wire:poll.30s class="space-y-4">
    <section class="user-history-hero" aria-label="{{ __('Attendance summary') }}">
        <div class="min-w-0">
            <p class="user-history-eyebrow">{{ __('Attendance') }}</p>
            <h2 class="user-history-title">{{ $displayMonth->translatedFormat('F Y') }}</h2>
            <p class="user-history-copy">
                {{ __('Working Days') }}: <span class="font-semibold text-slate-950 dark:text-white">{{ $workingDaysCount }}</span>
            </p>
        </div>

        <div class="user-history-score" role="status" aria-label="{{ __('Attendance rate') }} {{ $attendanceRate }}%">
            <span class="text-2xl font-bold leading-none">{{ $attendanceRate }}%</span>
            <span class="text-[0.65rem] font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Rate') }}</span>
        </div>
    </section>

    <section class="user-history-filters" aria-label="{{ __('Filter attendance history') }}">
        <x-user.tom-select-user id="selectedMonth" wire:model.live="selectedMonth" placeholder="{{ __('Month') }}" :options="$monthOptions" />
        <x-user.tom-select-user id="selectedYear" wire:model.live="selectedYear" placeholder="{{ __('Year') }}" :options="$yearOptions" />
    </section>

    <section class="user-history-calendar" aria-label="{{ __('Attendance calendar') }}">
        <div class="user-history-calendar__header">
            <div>
                <h3 class="text-base font-semibold tracking-tight text-slate-950 dark:text-white">{{ __('Monthly Calendar') }}</h3>
                <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('Tap a marked date to view details.') }}</p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-2.5 py-1 text-xs font-bold text-primary-700 dark:bg-primary-950/40 dark:text-primary-200">
                <span class="h-1.5 w-1.5 rounded-full bg-primary-500"></span>
                {{ __('Today') }}
            </span>
        </div>

        <div class="user-history-week-grid mt-4 text-center">
            @foreach ([__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')] as $index => $day)
                <div class="py-1 text-[0.65rem] font-bold uppercase tracking-[0.12em] {{ $index === 0 ? 'text-rose-500' : ($index === 5 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500') }}">
                    {{ $day }}
                </div>
            @endforeach
        </div>

        <div class="user-history-date-grid mt-1">
            @foreach ($dates as $date)
                @php
                    $isCurrentMonth = $date->month === $currentMonth;
                    $isToday = $date->isToday();
                    $dateKey = $date->format('Y-m-d');
                    $holiday = $holidays[$dateKey] ?? null;
                    $isHoliday = filled($holiday);
                    $attendance = $attendances->first(fn ($item) => $item->date->isSameDay($date));
                    $status = $attendance?->status?->value ?? ($date->isPast() && ! $date->isWeekend() && ! $isHoliday ? 'absent' : '-');
                    $approvalStatus = $attendance?->approval_status;
                    $style = $statusStyles[$status] ?? null;

                    if (in_array($status, ['excused', 'sick', 'permission', 'leave'], true) && $approvalStatus === 'pending') {
                        $style = ['dot' => 'bg-amber-300', 'ring' => 'ring-amber-400/30', 'label' => __('Pending')];
                    } elseif (in_array($status, ['excused', 'sick', 'permission', 'leave'], true) && $approvalStatus === 'rejected') {
                        $style = $statusStyles['rejected'];
                    }

                    $dayClass = $isCurrentMonth
                        ? 'bg-white/70 text-slate-800 ring-slate-200/70 hover:bg-white dark:bg-slate-950/32 dark:text-slate-100 dark:ring-slate-800/80 dark:hover:bg-slate-900/70'
                        : 'bg-slate-100/45 text-slate-400 ring-transparent opacity-45 dark:bg-slate-950/20 dark:text-slate-600';

                    if ($isHoliday && $isCurrentMonth) {
                        $dayClass = 'bg-rose-50/80 text-rose-600 ring-rose-100 hover:bg-rose-50 dark:bg-rose-950/18 dark:text-rose-300 dark:ring-rose-900/35';
                    } elseif ($date->isSunday() && $isCurrentMonth) {
                        $dayClass .= ' text-rose-500 dark:text-rose-300';
                    } elseif ($date->isFriday() && $isCurrentMonth) {
                        $dayClass .= ' text-emerald-600 dark:text-emerald-300';
                    }

                    $timeIn = $attendance?->time_in ? Carbon\Carbon::parse($attendance->time_in)->format('H:i') : null;
                    $ariaStatus = $style['label'] ?? ($isHoliday ? $holiday->name : __('No schedule'));
                @endphp

                <div class="aspect-square sm:aspect-[1.08/1]">
                    <button type="button"
                        @if ($attendance) wire:click="show({{ $attendance->id }})" @endif
                        class="user-history-day {{ $dayClass }} {{ $isToday ? 'user-history-day--today' : '' }} {{ $attendance ? 'cursor-pointer' : 'cursor-default' }}"
                        aria-label="{{ $date->translatedFormat('l, d F Y') }} - {{ $ariaStatus }}">
                        <span class="text-sm font-bold leading-none">{{ $date->day }}</span>

                        <span class="mt-auto flex min-h-4 items-center justify-center gap-1">
                            @if ($style && $status !== '-')
                                <span class="inline-flex h-2 w-2 rounded-full {{ $style['dot'] }} ring-4 {{ $style['ring'] }}"></span>
                            @elseif ($isHoliday && $isCurrentMonth)
                                <span class="text-[0.65rem] font-bold leading-none text-rose-500">{{ __('Holiday initial') }}</span>
                            @endif

                            @if ($timeIn && ! $isHoliday)
                                <span class="hidden text-[0.62rem] font-semibold leading-none text-slate-400 dark:text-slate-500 sm:inline">{{ $timeIn }}</span>
                            @endif
                        </span>
                    </button>
                </div>
            @endforeach
        </div>
    </section>

    <section aria-label="{{ __('Attendance summary') }}">
        <div class="user-history-summary">
            @foreach ($summaryCards as $stat)
                <div class="user-history-summary__item">
                    <span class="inline-flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full {{ $stat['bg'] }}"></span>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $stat['label'] }}</span>
                    </span>
                    <span class="text-lg font-bold leading-none {{ $stat['color'] }}">{{ $counts[$stat['key']] ?? 0 }}</span>
                </div>
            @endforeach
        </div>
    </section>

    @if ($holidays->isNotEmpty())
        <section class="user-history-panel" aria-label="{{ __('Holidays this Month') }}">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold tracking-tight text-slate-950 dark:text-white">{{ __('Holidays this Month') }}</h3>
                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-600 dark:bg-rose-950/28 dark:text-rose-300">{{ $holidays->count() }}</span>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($holidays->sortBy(fn ($holiday) => $holiday->date->day) as $holiday)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-rose-100 bg-rose-50/72 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/24 dark:text-rose-300">
                        <span>{{ $holiday->date->day }}</span>
                        <span class="max-w-[12rem] truncate opacity-80">{{ $holiday->name }}</span>
                    </span>
                @endforeach
            </div>
        </section>
    @endif

    <section class="user-history-panel" aria-label="{{ __('Legend') }}">
        <div class="flex flex-wrap gap-3 text-xs font-semibold text-slate-500 dark:text-slate-400">
            @foreach ([['bg-emerald-500', __('Present')], ['bg-amber-500', __('Late')], ['bg-sky-500', __('Excused')], ['bg-rose-600', __('Absent')], ['bg-primary-500', __('Today')]] as [$dot, $label])
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full {{ $dot }}"></span>
                    {{ $label }}
                </span>
            @endforeach
        </div>
    </section>

    <x-shared.attendance-detail-modal :current-attendance="$currentAttendance" />

    @stack('attendance-detail-scripts')
</div>
