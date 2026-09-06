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
        ['label' => __('Present'), 'key' => 'present', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-500'],
        ['label' => __('Late'), 'key' => 'late', 'color' => 'text-amber-600', 'bg' => 'bg-amber-500'],
        ['label' => __('Excused'), 'key' => 'excused', 'color' => 'text-sky-600', 'bg' => 'bg-sky-500'],
        ['label' => __('Sick'), 'key' => 'sick', 'color' => 'text-purple-600', 'bg' => 'bg-purple-500'],
        ['label' => __('Absent'), 'key' => 'absent', 'color' => 'text-rose-600', 'bg' => 'bg-rose-500'],
    ];
@endphp

<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="attendance-history-title" class="user-page-surface">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Attendance History')"
                title-id="attendance-history-title"
                module="attendance"
                class="border-b-0">
                <x-slot name="icon">
                    <x-heroicon-o-calendar-days class="h-5 w-5" />
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <div wire:poll.30s class="space-y-4">
                    <section class="user-history-hero" aria-label="{{ __('Attendance summary') }}">
        <div class="min-w-0">
            <p class="user-history-eyebrow">{{ __('Attendance') }}</p>
            <h2 class="user-history-title">{{ $displayMonth->translatedFormat('F Y') }}</h2>
            <p class="user-history-copy">
                {{ __('Working Days') }}: <span class="font-semibold text-slate-950">{{ $workingDaysCount }}</span>
            </p>
        </div>

        <div class="user-history-score" role="status" aria-label="{{ __('Attendance rate') }} {{ $attendanceRate }}%">
            <span class="text-2xl font-bold leading-none">{{ $attendanceRate }}%</span>
            <span class="text-[0.65rem] font-bold uppercase tracking-[0.16em] text-slate-500">{{ __('Rate') }}</span>
        </div>
    </section>

    <section class="user-history-filters" aria-label="{{ __('Filter attendance history') }}">
        <x-user.tom-select-user id="selectedMonth" wire:model.live="selectedMonth" placeholder="{{ __('Month') }}" :options="$monthOptions" />
        <x-user.tom-select-user id="selectedYear" wire:model.live="selectedYear" placeholder="{{ __('Year') }}" :options="$yearOptions" />
    </section>

    <section class="user-history-calendar" aria-label="{{ __('Attendance calendar') }}">
        <div class="user-history-calendar__header">
            <div>
                <h3 class="text-base font-semibold tracking-tight text-slate-950">{{ __('Monthly Calendar') }}</h3>
                <p class="mt-1 text-xs leading-5 text-slate-500">{{ __('Tap a marked date to view details.') }}</p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-bold text-cyan-700">
                <span class="h-1.5 w-1.5 rounded-full bg-module-attendance"></span>
                {{ __('Today') }}
            </span>
        </div>

        <div class="user-history-week-grid mt-4 text-center">
            @foreach ([__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')] as $index => $day)
                <div class="py-1 text-[0.65rem] font-bold uppercase tracking-[0.12em] {{ $index === 0 ? 'text-rose-700' : ($index === 5 ? 'text-emerald-700' : 'text-slate-600') }}">
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
                        ? 'bg-white text-slate-800 ring-slate-200/70 hover:bg-white'
                        : 'bg-slate-100 text-slate-400 ring-transparent opacity-45';

                    if ($isHoliday && $isCurrentMonth) {
                        $dayClass = 'bg-rose-50 text-rose-700 ring-rose-100 hover:bg-rose-50';
                    } elseif ($date->isSunday() && $isCurrentMonth) {
                        $dayClass .= ' text-rose-700';
                    } elseif ($date->isFriday() && $isCurrentMonth) {
                        $dayClass .= ' text-emerald-600';
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
                                <span class="text-[0.65rem] font-bold leading-none text-rose-700">{{ __('Holiday initial') }}</span>
                            @endif

                            @if ($timeIn && ! $isHoliday)
                                <span class="hidden text-[0.62rem] font-semibold leading-none text-slate-400 sm:inline">{{ $timeIn }}</span>
                            @endif

                            {{-- Request indicators (leave, overtime, reimbursement) --}}
                            @if (isset($requestsByDate[$dateKey]) && is_array($requestsByDate[$dateKey]))
                                @php
                                    $reqs = $requestsByDate[$dateKey];
                                    $reqStatuses = array_unique(array_column($reqs, 'status'));
                                    $pending = in_array('pending', $reqStatuses) || in_array('PENDING', $reqStatuses) || in_array('PENDING_FINANCE', $reqStatuses);
                                    $approved = in_array('approved', $reqStatuses) || in_array('APPROVED', $reqStatuses) || in_array('APPROVED_L1', $reqStatuses);
                                    $rejected = in_array('rejected', $reqStatuses) || in_array('REJECTED', $reqStatuses);
                                    $showPending = $pending && ! $approved;
                                    $showApproved = $approved && ! $rejected;
                                    $showRejected = $rejected;
                                @endphp
                                @if ($showPending)
                                    <span class="inline-flex h-2 w-2 rounded-full bg-amber-400 ring-2 ring-amber-400/30 animate-pulse"></span>
                                @elseif ($showApproved)
                                    <span class="inline-flex h-2 w-2 rounded-full bg-emerald-400 ring-2 ring-emerald-400/30"></span>
                                @elseif ($showRejected)
                                    <span class="inline-flex h-2 w-2 rounded-full bg-rose-400 ring-2 ring-rose-400/30"></span>
                                @endif
                            @endif
                        </span>
                    </button>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Riwayat Pengajuan --}}
    <section aria-label="{{ __('Request History') }}" class="user-history-panel mt-4">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold tracking-tight text-slate-950">{{ __('Request History') }}</h3>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ count($requests) }} pengajuan</span>
        </div>
        @if (count($requests) > 0)
            <div class="mt-3 space-y-2">
                @foreach ($requests as $req)
                    @php
                        $statusColors = [
                            'pending' => 'bg-amber-100 text-amber-700',
                            'PENDING' => 'bg-amber-100 text-amber-700',
                            'PENDING_FINANCE' => 'bg-amber-100 text-amber-700',
                            'approved' => 'bg-emerald-100 text-emerald-700',
                            'APPROVED' => 'bg-emerald-100 text-emerald-700',
                            'APPROVED_L1' => 'bg-emerald-100 text-emerald-700',
                            'rejected' => 'bg-rose-100 text-rose-700',
                            'REJECTED' => 'bg-rose-100 text-rose-700',
                        ];
                        $statusLabel = match ($req['status'] ?? '') {
                            'pending', 'PENDING', 'PENDING_FINANCE' => __('Pending'),
                            'approved', 'APPROVED', 'APPROVED_L1' => __('Approved'),
                            'rejected', 'REJECTED' => __('Rejected'),
                            default => $req['status'] ?? __('Unknown'),
                        };
                        $colorClass = $statusColors[$req['status']] ?? 'bg-slate-100 text-slate-600';
                        $typeIcons = [
                            'leave' => '📋',
                            'overtime' => '⏰',
                            'reimbursement' => '💰',
                        ];
                        $icon = $typeIcons[$req['type']] ?? '📄';
                    @endphp
                    <div class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50 transition">
                        <span class="text-base">{{ $icon }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-900 truncate">{{ $req['title'] ?? $req['type'] }}</p>
                            <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($req['date'])->translatedFormat('d F Y') }} @if (!empty($req['end_date']) && $req['end_date'] !== $req['date']) — {{ \Carbon\Carbon::parse($req['end_date'])->translatedFormat('d F Y') }} @endif</p>
                            @if (!empty($req['approvals']) && is_array($req['approvals']))
                                <p class="text-[0.65rem] text-slate-400">
                                    @foreach ($req['approvals'] as $approval)
                                        @if (!empty($approval['level']))
                                            Level {{ $approval['level'] }}: {{ $approval['status'] ?? '-' }}
                                            @if (!empty($approval['approved_by'])) → @endif
                                            @if (!empty($approval['approved_by'])) , @endif
                                        @endif
                                    @endforeach
                                </p>
                            @endif
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $colorClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="mt-3 text-sm text-slate-500">{{ __('No requests this month.') }}</p>
        @endif
    </section>

    <section aria-label="{{ __('Attendance summary') }}">
        <div class="user-history-summary">
            @foreach ($summaryCards as $stat)
                <div class="user-history-summary__item">
                    <span class="inline-flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full {{ $stat['bg'] }}"></span>
                        <span class="text-sm font-semibold text-slate-700">{{ $stat['label'] }}</span>
                    </span>
                    <span class="text-lg font-bold leading-none {{ $stat['color'] }}">{{ $counts[$stat['key']] ?? 0 }}</span>
                </div>
            @endforeach
        </div>
    </section>

    @if ($holidays->isNotEmpty())
        <section class="user-history-panel" aria-label="{{ __('Holidays this Month') }}">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold tracking-tight text-slate-950">{{ __('Holidays this Month') }}</h3>
                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-600">{{ $holidays->count() }}</span>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($holidays->sortBy(fn ($holiday) => $holiday->date->day) as $holiday)
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-rose-100 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                        <span>{{ $holiday->date->day }}</span>
                        <span class="max-w-[12rem] truncate opacity-80">{{ $holiday->name }}</span>
                    </span>
                @endforeach
            </div>
        </section>
    @endif

    <section class="user-history-panel" aria-label="{{ __('Legend') }}">
        <div class="flex flex-wrap gap-3 text-xs font-semibold text-slate-500">
            @foreach ([['bg-emerald-500', __('Present')], ['bg-amber-500', __('Late')], ['bg-sky-500', __('Excused')], ['bg-rose-600', __('Absent')], ['bg-primary-500', __('Today')]] as [$dot, $label])
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full {{ $dot }}"></span>
                    {{ $label }}
                </span>
            @endforeach
        </div>
    </section>

    <x-shared.attendance-detail-modal :current-attendance="$currentAttendance" />
                </div>
            </div>
        </section>
    </div>
</div>
