<x-layouts::app.sidebar :title="__('Dashboard')">
    @php
        $user = auth()->user();
        $employee = $user->employee;
        $role = $user->getRoleNames()->first() ?? 'employee';

        // Today's attendance status
        $todayAttendance = $employee?->attendances()->whereDate('date', today())->first();
        $hasCheckedIn = $todayAttendance?->clock_in !== null;
        $hasCheckedOut = $todayAttendance?->clock_out !== null;

        // Pending counts
        $pendingLeaves = $employee?->leaves()->where('status', 'pending')->count() ?? 0;
        $pendingOvertimes = $employee?->overtimes()->where('status', 'pending')->count() ?? 0;
        $pendingReimbursements = $employee?->reimbursements()->where('status', 'pending')->count() ?? 0;

        // Upcoming shift schedule
        $upcomingShift = $employee?->shiftSchedules()
            ->where('date', '>=', today())
            ->orderBy('date')
            ->first();

        // Face enrollment status
        $hasFaceEnrolled = $employee
            ? app(\App\Services\FaceRecognitionService::class)->hasFaceEnrolled($employee)
            : false;

        // Stats for stats row (Hadir, Terlambat, Alpa, Pending Pengajuan)
        $totalAttendances = $employee?->attendances()->count() ?? 0;
        $onTimeCount = $employee?->attendances()->whereIn('status', ['on_time', 'permission', 'holiday'])->count() ?? 0;
        $lateCount = $employee?->attendances()->where('status', 'late')->count() ?? 0;
        $absentCount = $employee?->attendances()->whereIn('status', ['absent', 'missed_clock_in', 'missed_clock_out'])->count() ?? 0;
        $totalPending = $pendingLeaves + $pendingOvertimes + $pendingReimbursements;

        // Status badge for today
        $todayStatus = $hasCheckedOut ? 'done' : ($hasCheckedIn ? 'checked_in' : 'pending');
        $todayStatusConfig = match ($todayStatus) {
            'done' => ['label' => __('Selesai'), 'icon' => 'check_circle', 'tone' => 'success'],
            'checked_in' => ['label' => __('Sudah Masuk'), 'icon' => 'login', 'tone' => 'info'],
            default => ['label' => __('Belum Masuk'), 'icon' => 'schedule', 'tone' => 'warning'],
        };

        // Quick action items config
        $quickActions = [
            [
                'route' => 'leaves.apply',
                'icon' => 'event_note',
                'label' => __('Cuti'),
                'subtitle' => $pendingLeaves > 0 ? "{$pendingLeaves} " . __('menunggu') : __('Ajukan cuti'),
                'icon_tone' => 'info',
            ],
            [
                'route' => 'overtimes.apply',
                'icon' => 'schedule',
                'label' => __('Lembur'),
                'subtitle' => $pendingOvertimes > 0 ? "{$pendingOvertimes} " . __('menunggu') : __('Ajukan lembur'),
                'icon_tone' => 'warning',
            ],
            [
                'route' => 'reimbursements.apply',
                'icon' => 'receipt_long',
                'label' => __('Klaim'),
                'subtitle' => $pendingReimbursements > 0 ? "{$pendingReimbursements} " . __('menunggu') : __('Ajukan klaim'),
                'icon_tone' => 'error',
            ],
        ];

        // Upcoming shift badge
        $shiftBadge = null;
        if ($upcomingShift) {
            if ($upcomingShift->date->isToday()) {
                $shiftBadge = ['label' => __('Hari ini'), 'tone' => 'primary'];
            } elseif ($upcomingShift->date->isTomorrow()) {
                $shiftBadge = ['label' => __('Besok'), 'tone' => 'warning'];
            }
        }
    @endphp

    <div class="space-y-6 animate-fade-in-up" style="--stagger-delay: 0ms;">

        {{-- 0. FACE ENROLLMENT ONBOARDING BANNER --}}
        @if ($employee && ! $hasFaceEnrolled)
            <section class="flex flex-col gap-4 rounded-2xl border border-primary/20 bg-primary/5 p-5 sm:flex-row sm:items-center sm:justify-between animate-slide-in" style="--stagger-delay: 50ms;">
                <div class="flex items-start gap-4">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary text-on-primary shadow-soft">
                        <span class="material-symbols-outlined">face</span>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-ink">{{ __('Daftarkan Wajah Anda') }}</h2>
                        <p class="mt-0.5 text-sm text-on-surface-variant">
                            {{ __('Belum mendaftarkan wajah. Daftar sekarang untuk absen cepat dengan Face ID. Tanpa Face ID, absen pakai PIN.') }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('attendance.face-registration') }}"
                   class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 font-semibold text-on-primary shadow-soft transition-smooth hover:bg-primary-deep hover:shadow-modal">
                    <span class="material-symbols-outlined text-lg">add_a_photo</span>
                    {{ __('Daftar Wajah') }}
                </a>
            </section>
        @endif

        {{-- 1. HERO SECTION: Clock In/Out CTA DOMINAN --}}
        <section class="relative overflow-hidden rounded-2xl border border-outline-variant/50 bg-canvas p-6 shadow-soft transition-smooth hover:shadow-modal animate-slide-in" style="--stagger-delay: 100ms;">
            {{-- Subtle gradient accent border top --}}
            <div class="absolute inset-x-0 top-0 h-1 rounded-t-2xl" style="background: linear-gradient(90deg, var(--color-primary), var(--color-primary-bright));"></div>

            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="ess-eyebrow">
                        {{ __('Hari ini') }} • {{ now()->translatedFormat('l, d F Y') }}
                    </p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-ink sm:text-3xl">
                        {{ $user->name }}, {{ $hasCheckedIn ? __('selamat bekerja') : __('siap memulai hari?') }}
                    </h1>

                    {{-- Status badge + clock times --}}
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        {{-- Status Badge --}}
                        @php
                            $statusBadgeClasses = match($todayStatusConfig['tone']) {
                                'success' => 'bg-success/10 text-success',
                                'warning' => 'bg-warning/10 text-warning',
                                'error' => 'bg-error/10 text-error',
                                'info' => 'bg-info/10 text-info',
                                default => 'bg-surface-container-high text-on-surface-variant',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium {{ $statusBadgeClasses }} transition-smooth">
                            <span class="material-symbols-outlined text-sm">{{ $todayStatusConfig['icon'] }}</span>
                            {{ $todayStatusConfig['label'] }}
                        </span>

                        {{-- Clock In time --}}
                        @if ($todayAttendance?->clock_in)
                            <div class="flex items-center gap-2 rounded-xl bg-surface-container-low px-3 py-2 text-sm">
                                <span class="material-symbols-outlined text-primary text-lg">login</span>
                                <span class="font-medium text-ink">{{ \Carbon\Carbon::parse($todayAttendance->clock_in)->format('H:i') }}</span>
                                <span class="text-on-surface-variant">{{ __('Masuk') }}</span>
                            </div>
                        @endif

                        {{-- Clock Out time --}}
                        @if ($todayAttendance?->clock_out)
                            <div class="flex items-center gap-2 rounded-xl bg-success/10 px-3 py-2 text-sm">
                                <span class="material-symbols-outlined text-success text-lg">logout</span>
                                <span class="font-medium text-ink">{{ \Carbon\Carbon::parse($todayAttendance->clock_out)->format('H:i') }}</span>
                                <span class="text-on-surface-variant">{{ __('Pulang') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Primary CTA: Clock In/Out --}}
                <div class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto">
                    @if (!$hasCheckedIn)
                        <a href="{{ route('attendance.clock-in') }}"
                           class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-8 py-4 font-semibold text-on-primary shadow-soft transition-smooth hover:bg-primary-deep hover:shadow-modal hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                           style="min-height: 56px;">
                            <span class="material-symbols-outlined group-hover:translate-x-0.5 transition-transform">login</span>
                            <span class="text-lg">{{ __('Clock In') }}</span>
                        </a>
                    @elseif (!$hasCheckedOut)
                        <a href="{{ route('attendance.clock-in') }}"
                           class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-8 py-4 font-semibold text-on-primary shadow-soft transition-smooth hover:bg-primary-deep hover:shadow-modal hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                           style="min-height: 56px;">
                            <span class="material-symbols-outlined group-hover:-translate-x-0.5 transition-transform">logout</span>
                            <span class="text-lg">{{ __('Clock Out') }}</span>
                        </a>
                    @else
                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-success/10 px-8 py-4 font-semibold text-success transition-smooth cursor-default"
                                disabled style="min-height: 56px;">
                            <span class="material-symbols-outlined">check_circle</span>
                            <span class="text-lg">{{ __('Hari ini selesai') }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </section>

        {{-- 2. STATS ROW: 4 Stat Cards --}}
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4" role="region" aria-label="{{ __('Ringkasan Absensi') }}">
            {{-- Hadir --}}
            <article class="stat-card group relative overflow-hidden rounded-2xl border border-outline-variant/50 bg-canvas p-5 shadow-soft transition-all hover:shadow-modal hover:-translate-y-0.5 animate-slide-in" style="--stagger-delay: 150ms;">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="ess-stat__label">{{ __('Hadir') }}</p>
                        <p class="ess-stat__value tabular-nums text-3xl">{{ $onTimeCount }}</p>
                        <p class="mt-1 text-xs text-on-surface-variant">{{ __('dari') }} {{ $totalAttendances }} {{ __('hari') }}</p>
                    </div>
                    <div class="stat-icon tone-success stat-icon-green shrink-0" aria-hidden="true">
                        <span class="material-symbols-outlined text-2xl">check_circle</span>
                    </div>
                </div>
                <div class="absolute inset-x-0 top-0 h-1 rounded-t-2xl" style="background: linear-gradient(90deg, var(--color-success), var(--color-success));"></div>
            </article>

            {{-- Terlambat --}}
            <article class="stat-card group relative overflow-hidden rounded-2xl border border-outline-variant/50 bg-canvas p-5 shadow-soft transition-all hover:shadow-modal hover:-translate-y-0.5 animate-slide-in" style="--stagger-delay: 200ms;">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="ess-stat__label">{{ __('Terlambat') }}</p>
                        <p class="ess-stat__value tabular-nums text-3xl">{{ $lateCount }}</p>
                        <p class="mt-1 text-xs text-on-surface-variant">{{ $totalAttendances > 0 ? round(($lateCount / $totalAttendances) * 100) : 0 }}% {{ __('dari total') }}</p>
                    </div>
                    <div class="stat-icon tone-warning stat-icon-amber shrink-0" aria-hidden="true">
                        <span class="material-symbols-outlined text-2xl">schedule</span>
                    </div>
                </div>
                <div class="absolute inset-x-0 top-0 h-1 rounded-t-2xl" style="background: linear-gradient(90deg, var(--color-warning), var(--color-warning));"></div>
            </article>

            {{-- Alpa --}}
            <article class="stat-card group relative overflow-hidden rounded-2xl border border-outline-variant/50 bg-canvas p-5 shadow-soft transition-all hover:shadow-modal hover:-translate-y-0.5 animate-slide-in" style="--stagger-delay: 250ms;">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="ess-stat__label">{{ __('Alpa') }}</p>
                        <p class="ess-stat__value tabular-nums text-3xl">{{ $absentCount }}</p>
                        <p class="mt-1 text-xs text-on-surface-variant">{{ $absentCount === 0 ? __('Lancar!') : __('Perlu perhatian') }}</p>
                    </div>
                    <div class="stat-icon tone-error stat-icon-coral shrink-0" aria-hidden="true">
                        <span class="material-symbols-outlined text-2xl">cancel</span>
                    </div>
                </div>
                <div class="absolute inset-x-0 top-0 h-1 rounded-t-2xl" style="background: linear-gradient(90deg, var(--color-error), var(--color-error));"></div>
            </article>

            {{-- Pending Pengajuan --}}
            <article class="stat-card group relative overflow-hidden rounded-2xl border border-outline-variant/50 bg-canvas p-5 shadow-soft transition-all hover:shadow-modal hover:-translate-y-0.5 animate-slide-in" style="--stagger-delay: 300ms;">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="ess-stat__label">{{ __('Pending Pengajuan') }}</p>
                        <p class="ess-stat__value tabular-nums text-3xl">{{ $totalPending }}</p>
                        <p class="mt-1 text-xs text-on-surface-variant">
                            {{ $pendingLeaves > 0 ? "{$pendingLeaves} " . __('cuti') . ', ' : '' }}
                            {{ $pendingOvertimes > 0 ? "{$pendingOvertimes} " . __('lembur') . ', ' : '' }}
                            {{ $pendingReimbursements > 0 ? "{$pendingReimbursements} " . __('klaim') : '' }}
                        </p>
                    </div>
                    <div class="stat-icon tone-primary stat-icon-blue shrink-0" aria-hidden="true">
                        <span class="material-symbols-outlined text-2xl">pending_actions</span>
                    </div>
                </div>
                <div class="absolute inset-x-0 top-0 h-1 rounded-t-2xl" style="background: linear-gradient(90deg, var(--color-primary), var(--color-primary-bright));"></div>
            </article>
        </section>

        {{-- 3. QUICK ACTIONS GRID (3 items) --}}
        <section class="animate-slide-in" style="--stagger-delay: 350ms;">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="ess-section-title">{{ __('Akses Cepat') }}</h2>
            </div>

            <div class="grid grid-cols-3 gap-4">
                @foreach ($quickActions as $index => $action)
                    @php
                        $iconToneClasses = match($action['icon_tone']) {
                            'success' => 'bg-success/10 text-success group-hover:bg-success group-hover:text-on-success',
                            'warning' => 'bg-warning/10 text-warning group-hover:bg-warning group-hover:text-on-warning',
                            'error' => 'bg-error/10 text-error group-hover:bg-error group-hover:text-on-error',
                            'info' => 'bg-info/10 text-info group-hover:bg-info group-hover:text-on-info',
                            'primary' => 'bg-primary/10 text-primary group-hover:bg-primary group-hover:text-on-primary',
                            default => 'bg-surface-container-high text-on-surface-variant group-hover:bg-surface-container-high group-hover:text-ink',
                        };
                    @endphp
                    <a href="{{ route($action['route']) }}"
                       class="group relative overflow-hidden rounded-2xl border border-outline-variant/50 bg-canvas p-5 shadow-soft transition-all duration-300 hover:border-primary/30 hover:shadow-modal hover:-translate-y-1 animate-slide-in"
                       style="--stagger-delay: {{ 400 + ($index * 50) }}ms;">
                        {{-- Icon container with semantic tone --}}
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl transition-all duration-300 group-hover:shadow-lg {{ $iconToneClasses }}">
                            <span class="material-symbols-outlined text-2xl transition-transform duration-300 group-hover:scale-110">{{ $action['icon'] }}</span>
                        </div>

                        <p class="mt-4 text-sm font-medium text-ink">{{ $action['label'] }}</p>
                        <p class="mt-1 text-xs text-on-surface-variant">{{ $action['subtitle'] }}</p>

                        {{-- Subtle shimmer on hover --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500" aria-hidden="true"></div>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- 4. UPCOMING SHIFT --}}
        @if ($upcomingShift)
            <section class="animate-slide-in" style="--stagger-delay: 550ms;">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="ess-section-title">{{ __('Jadwal Berikutnya') }}</h2>
                    <a href="{{ route('my-schedule') }}" class="text-sm font-medium text-primary hover:underline">{{ __('Lihat semua') }}</a>
                </div>
                <div class="relative overflow-hidden rounded-2xl border border-outline-variant/50 bg-canvas p-5 shadow-soft transition-smooth hover:shadow-modal">
                    <div class="absolute inset-x-0 top-0 h-1 rounded-t-2xl" style="background: linear-gradient(90deg, var(--color-primary), var(--color-primary-bright));"></div>

                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <span class="material-symbols-outlined text-3xl">event</span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-ink truncate">{{ $upcomingShift->name ?? __('Shift') }}</p>
                                <p class="mt-1 text-sm text-on-surface-variant">
                                    {{ \Carbon\Carbon::parse($upcomingShift->date)->translatedFormat('l, d M Y') }}
                                    @if ($upcomingShift->start_time && $upcomingShift->end_time)
                                        • {{ \Carbon\Carbon::parse($upcomingShift->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($upcomingShift->end_time)->format('H:i') }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if ($shiftBadge)
                            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-medium {{ match($shiftBadge['tone']) {
                                'primary' => 'bg-primary/10 text-primary',
                                'warning' => 'bg-warning/10 text-warning',
                                'error' => 'bg-error/10 text-error',
                                'info' => 'bg-info/10 text-info',
                                default => 'bg-surface-container-high text-on-surface-variant',
                            } }}">
                                {{ $shiftBadge['label'] }}
                            </span>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- 5. RECENT ACTIVITY --}}
        <section class="animate-slide-in" style="--stagger-delay: 600ms;">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="ess-section-title">{{ __('Aktivitas Terbaru') }}</h2>
                <a href="{{ route('attendance.index') }}" class="text-sm font-medium text-primary hover:underline">{{ __('Lihat riwayat') }}</a>
            </div>

            <div class="overflow-hidden rounded-2xl border border-outline-variant/50 bg-canvas shadow-soft">
                @if ($employee && $employee->attendances()->count() > 0)
                    <div class="divide-y divide-outline-variant/50">
                        @foreach ($employee->attendances()->latest()->take(5)->get() as $index => $att)
                            @php
                                $statusValue = $att->status instanceof \App\Enums\AttendanceStatus
                                    ? $att->status->value
                                    : ($att->status ?? 'unknown');
                                $tone = match($statusValue) {
                                    'on_time', 'permission', 'holiday' => 'success',
                                    'late', 'early' => 'warning',
                                    'absent', 'missed_clock_in', 'missed_clock_out' => 'error',
                                    default => 'neutral',
                                };
                                $icon = match($statusValue) {
                                    'on_time', 'permission', 'holiday' => 'check_circle',
                                    'late', 'early' => 'schedule',
                                    'absent', 'missed_clock_in', 'missed_clock_out' => 'cancel',
                                    default => 'help_outline',
                                };
                                $statusLabel = $att->status instanceof \App\Enums\AttendanceStatus
                                    ? $att->status->label()
                                    : ($statusValue ?? '—');
                            @endphp
                            <div class="flex items-center justify-between p-4 transition-smooth hover:bg-surface-container-low/50 animate-slide-in" style="--stagger-delay: {{ 650 + ($index * 50) }}ms;">
                                <div class="flex items-center gap-4 min-w-0">
                                    @php
                                        $toneClasses = match($tone) {
                                            'success' => 'bg-success/10 text-success',
                                            'warning' => 'bg-warning/10 text-warning',
                                            'error' => 'bg-error/10 text-error',
                                            'info' => 'bg-info/10 text-info',
                                            'neutral' => 'bg-surface-container-high text-on-surface-variant',
                                            default => 'bg-surface-container-high text-on-surface-variant',
                                        };
                                        $badgeToneClasses = match($tone) {
                                            'success' => 'bg-success/10 text-success',
                                            'warning' => 'bg-warning/10 text-warning',
                                            'error' => 'bg-error/10 text-error',
                                            'info' => 'bg-info/10 text-info',
                                            'neutral' => 'bg-surface-container-high text-on-surface-variant',
                                            default => 'bg-surface-container-high text-on-surface-variant',
                                        };
                                    @endphp
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $toneClasses }}">
                                        <span class="material-symbols-outlined text-sm">{{ $icon }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-ink truncate">{{ \Carbon\Carbon::parse($att->date)->translatedFormat('d M Y') }}</p>
                                        <p class="text-xs text-on-surface-variant">
                                            {{ $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '—' }}
                                            @if ($att->clock_out) – {{ \Carbon\Carbon::parse($att->clock_out)->format('H:i') }} @endif
                                        </p>
                                    </div>
                                </div>
                                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-medium {{ $badgeToneClasses }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center">
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant/30">history</span>
                        <p class="mt-2 text-on-surface-variant">{{ __('Belum ada riwayat absensi') }}</p>
                    </div>
                @endif
            </div>
        </section>

    </div>
</x-layouts::app.sidebar>

{{-- Staggered animation styles --}}
@push('styles')
<style>
    /* Staggered entrance animations using CSS custom properties */
    .animate-slide-in {
        opacity: 0;
        transform: translateY(16px);
        animation: slideIn var(--motion-duration-normal, 250ms) var(--motion-easing-decelerated, cubic-bezier(0, 0, 0.2, 1)) forwards;
        animation-delay: var(--stagger-delay, 0ms);
    }

    .animate-fade-in-up {
        opacity: 0;
        transform: translateY(8px);
        animation: fadeInUp var(--motion-duration-normal, 250ms) var(--motion-easing-decelerated, cubic-bezier(0, 0, 0.2, 1)) forwards;
    }

    @keyframes slideIn {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Respect reduced motion */
    @media (prefers-reduced-motion: reduce) {
        .animate-slide-in,
        .animate-fade-in-up {
            animation: none !important;
            opacity: 1 !important;
            transform: none !important;
        }
    }

    /* Stat icon gradient backgrounds using MD3 semantic tokens */
    .stat-icon {
        @apply flex size-12 items-center justify-center rounded-xl text-white shadow-sm;
    }
    .stat-icon-blue { background: linear-gradient(135deg, var(--color-primary), var(--color-primary-bright)); }
    .stat-icon-green { background: linear-gradient(135deg, var(--color-success), var(--color-success)); }
    .stat-icon-amber { background: linear-gradient(135deg, var(--color-warning), var(--color-warning)); }
    .stat-icon-coral { background: linear-gradient(135deg, var(--color-error), var(--color-error)); }
    .stat-icon-purple { background: linear-gradient(135deg, var(--color-primary), var(--color-primary-bright)); }
</style>
@endpush