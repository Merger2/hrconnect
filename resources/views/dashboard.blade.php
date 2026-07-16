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

        // Face enrollment status — drives the "daftar wajah" onboarding banner
        $hasFaceEnrolled = $employee
            ? app(\App\Services\FaceRecognitionService::class)->hasFaceEnrolled($employee)
            : false;
    @endphp

    <div class="space-y-6">

        {{-- 0. FACE ENROLLMENT ONBOARDING BANNER --}}
        @if ($employee && ! $hasFaceEnrolled)
            <section class="flex flex-col gap-4 rounded-2xl border border-primary/20 bg-primary-soft p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary text-on-primary shadow-soft">
                        <span class="material-symbols-outlined">face</span>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-ink">{{ __('Daftarkan Wajah Anda') }}</h2>
                        <p class="mt-0.5 text-sm text-on-surface-variant">
                            {{ __('Anda belum mendaftarkan wajah. Daftar sekarang agar bisa absen cepat dengan Face ID. Tanpa Face ID, Anda harus absen pakai PIN.') }}
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


        {{-- 1. HERO SECTION: Today at a Glance --}}
        <section class="ess-card p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="ess-eyebrow">
                        {{ __('Hari ini') }} • {{ now()->translatedFormat('l, d F Y') }}
                    </p>
                    <h1 class="mt-1 text-xl font-semibold tracking-tight text-ink sm:text-2xl">
                        {{ $user->name }}, {{ $hasCheckedIn ? __('selamat bekerja') : __('siap memulai hari?') }}
                    </h1>
                </div>

                {{-- Primary CTA: Clock In/Out --}}
                <div class="flex w-full flex-col gap-3 sm:flex-row lg:w-auto">
                    @if (!$hasCheckedIn)
                        <a href="{{ route('attendance.clock-in') }}"
                           class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 font-semibold text-on-primary shadow-soft transition-smooth hover:bg-primary-deep hover:shadow-modal">
                            <span class="material-symbols-outlined">login</span>
                            {{ __('Clock In') }}
                        </a>
                    @elseif (!$hasCheckedOut)
                        <a href="{{ route('attendance.clock-in') }}"
                           class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 font-semibold text-on-primary shadow-soft transition-smooth hover:bg-primary-deep hover:shadow-modal">
                            <span class="material-symbols-outlined">logout</span>
                            {{ __('Clock Out') }}
                        </a>
                    @else
                        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-success/10 font-semibold text-success" disabled>
                            <span class="material-symbols-outlined">check_circle</span>
                            {{ __('Hari ini selesai') }}
                        </button>
                    @endif
                </div>
            </div>

            {{-- Today's Timeline --}}
            <div class="mt-6 border-t border-outline-variant/60 pt-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex items-center gap-3 rounded-xl p-4 {{ $hasCheckedIn ? 'bg-success/10' : 'bg-surface-container-high' }}">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $hasCheckedIn ? 'bg-success/10 text-success' : 'bg-surface-dim text-on-surface-variant' }}">
                            <span class="material-symbols-outlined text-lg">{{ $hasCheckedIn ? 'check' : 'schedule' }}</span>
                        </div>
                        <div>
                            <p class="text-xs text-on-surface-variant">{{ __('Check In') }}</p>
                            <p class="font-medium text-ink">
                                {{ $todayAttendance?->clock_in ? \Carbon\Carbon::parse($todayAttendance->clock_in)->format('H:i') : '—' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 rounded-xl p-4 {{ $hasCheckedOut ? 'bg-success/10' : 'bg-surface-container-high' }}">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $hasCheckedOut ? 'bg-success/10 text-success' : 'bg-surface-dim text-on-surface-variant' }}">
                            <span class="material-symbols-outlined text-lg">{{ $hasCheckedOut ? 'check' : 'schedule' }}</span>
                        </div>
                        <div>
                            <p class="text-xs text-on-surface-variant">{{ __('Check Out') }}</p>
                            <p class="font-medium text-ink">
                                {{ $todayAttendance?->clock_out ? \Carbon\Carbon::parse($todayAttendance->clock_out)->format('H:i') : '—' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 2. QUICK ACTIONS GRID --}}
        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold tracking-tight text-ink">{{ __('Akses Cepat') }}</h2>
            </div>

            <div class="grid grid-cols-2 gap-5 lg:grid-cols-4 lg:gap-6">
                {{-- Clock In --}}
                <a href="{{ route('attendance.clock-in') }}"
                   class="group rounded-xl border border-outline-variant bg-canvas p-5 shadow-soft transition-smooth hover:border-primary/30 hover:shadow-modal">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-soft text-primary transition-smooth group-hover:bg-primary group-hover:text-on-primary">
                        <span class="material-symbols-outlined">badge</span>
                    </div>
                    <p class="mt-4 text-sm font-medium text-ink">{{ __('Absen') }}</p>
                    <p class="text-xs text-on-surface-variant">{{ __('Wajah & GPS') }}</p>
                </a>

                {{-- Leave --}}
                <a href="{{ route('leaves.apply') }}"
                   class="group rounded-xl border border-outline-variant bg-canvas p-5 shadow-soft transition-smooth hover:border-primary/30 hover:shadow-modal">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-soft text-primary transition-smooth group-hover:bg-primary group-hover:text-on-primary">
                        <span class="material-symbols-outlined">event_note</span>
                    </div>
                    <p class="mt-4 text-sm font-medium text-ink">{{ __('Cuti') }}</p>
                    <p class="text-xs text-on-surface-variant">{{ $pendingLeaves > 0 ? "{$pendingLeaves} menunggu" : __('Ajukan cuti') }}</p>
                </a>

                {{-- Overtime --}}
                <a href="{{ route('overtimes.apply') }}"
                   class="group rounded-xl border border-outline-variant bg-canvas p-5 shadow-soft transition-smooth hover:border-primary/30 hover:shadow-modal">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-soft text-primary transition-smooth group-hover:bg-primary group-hover:text-on-primary">
                        <span class="material-symbols-outlined">schedule</span>
                    </div>
                    <p class="mt-4 text-sm font-medium text-ink">{{ __('Lembur') }}</p>
                    <p class="text-xs text-on-surface-variant">{{ $pendingOvertimes > 0 ? "{$pendingOvertimes} menunggu" : __('Ajukan lembur') }}</p>
                </a>

                {{-- Reimbursement --}}
                <a href="{{ route('reimbursements.apply') }}"
                   class="group rounded-xl border border-outline-variant bg-canvas p-5 shadow-soft transition-smooth hover:border-primary/30 hover:shadow-modal">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-soft text-primary transition-smooth group-hover:bg-primary group-hover:text-on-primary">
                        <span class="material-symbols-outlined">receipt_long</span>
                    </div>
                    <p class="mt-4 text-sm font-medium text-ink">{{ __('Klaim') }}</p>
                    <p class="text-xs text-on-surface-variant">{{ $pendingReimbursements > 0 ? "{$pendingReimbursements} menunggu" : __('Ajukan klaim') }}</p>
                </a>
            </div>

            {{-- Secondary Actions Row --}}
            <div class="mt-4 grid grid-cols-2 gap-5 lg:grid-cols-4 lg:gap-6">
                <a href="{{ route('payroll.index') }}" class="group rounded-xl border border-outline-variant bg-canvas p-5 text-center shadow-soft transition-smooth hover:border-primary/20 hover:shadow-modal">
                    <span class="material-symbols-outlined text-primary">payments</span>
                    <p class="mt-2 text-xs font-medium text-ink">{{ __('Slip Gaji') }}</p>
                </a>
                <a href="{{ route('loans.index') }}" class="group rounded-xl border border-outline-variant bg-canvas p-5 text-center shadow-soft transition-smooth hover:border-primary/20 hover:shadow-modal">
                    <span class="material-symbols-outlined text-account_balance">account_balance</span>
                    <p class="mt-2 text-xs font-medium text-ink">{{ __('Pinjaman') }}</p>
                </a>
                <a href="{{ route('assets.index') }}" class="group rounded-xl border border-outline-variant bg-canvas p-5 text-center shadow-soft transition-smooth hover:border-primary/20 hover:shadow-modal">
                    <span class="material-symbols-outlined text-primary">laptop</span>
                    <p class="mt-2 text-xs font-medium text-ink">{{ __('Aset') }}</p>
                </a>
                <a href="{{ route('knowledge-base.index') }}" class="group rounded-xl border border-outline-variant bg-canvas p-5 text-center shadow-soft transition-smooth hover:border-primary/20 hover:shadow-modal">
                    <span class="material-symbols-outlined text-primary">smart_toy</span>
                    <p class="mt-2 text-xs font-medium text-ink">{{ __('AI Chat') }}</p>
                </a>
            </div>
        </section>

        {{-- 3. UPCOMING SHIFT / SCHEDULE --}}
        @if ($upcomingShift)
        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold tracking-tight text-ink">{{ __('Jadwal Berikutnya') }}</h2>
                <a href="{{ route('my-schedule') }}" class="text-sm font-medium text-primary hover:underline">{{ __('Lihat semua') }}</a>
            </div>
            <div class="rounded-xl border border-outline-variant bg-canvas p-5 shadow-soft">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-primary-soft text-primary">
                            <span class="material-symbols-outlined text-2xl">event</span>
                        </div>
                        <div>
                            <p class="font-medium text-ink">{{ $upcomingShift->name ?? __('Shift') }}</p>
                            <p class="text-sm text-on-surface-variant">
                                {{ \Carbon\Carbon::parse($upcomingShift->date)->translatedFormat('l, d M Y') }}
                                @if ($upcomingShift->start_time && $upcomingShift->end_time)
                                    • {{ \Carbon\Carbon::parse($upcomingShift->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($upcomingShift->end_time)->format('H:i') }}
                                @endif
                            </p>
                        </div>
                    </div>
                    @if ($upcomingShift->date->isToday())
                        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                            {{ __('Hari ini') }}
                        </span>
                    @elseif ($upcomingShift->date->isTomorrow())
                        <span class="rounded-full bg-warning/10 px-3 py-1 text-xs font-medium text-warning">
                            {{ __('Besok') }}
                        </span>
                    @endif
                </div>
            </div>
        </section>
        @endif

        {{-- 4. RECENT ACTIVITY --}}
        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold tracking-tight text-ink">{{ __('Aktivitas Terbaru') }}</h2>
                <a href="{{ route('attendance.index') }}" class="text-sm font-medium text-primary hover:underline">{{ __('Lihat riwayat') }}</a>
            </div>
            <div class="overflow-hidden rounded-xl border border-outline-variant bg-canvas shadow-soft">
                @if ($employee && $employee->attendances()->count() > 0)
                    <div class="divide-y divide-outline-variant/50">
                        @foreach ($employee->attendances()->latest()->take(5)->get() as $att)
                            @php
                                $statusValue = $att->status instanceof \App\Enums\AttendanceStatus
                                    ? $att->status->value
                                    : ($att->status ?? 'unknown');
                                $statusColor = match($statusValue) {
                                    'on_time' => 'success',
                                    'late' => 'warning',
                                    'absent', 'missed_clock_in', 'missed_clock_out' => 'error',
                                    default => 'surface-dim',
                                };
                                $statusIcon = match($statusValue) {
                                    'on_time' => 'check_circle',
                                    'late' => 'schedule',
                                    'absent', 'missed_clock_in', 'missed_clock_out' => 'cancel',
                                    default => 'help_outline',
                                };
                                $statusLabel = $att->status instanceof \App\Enums\AttendanceStatus
                                    ? $att->status->label()
                                    : ($statusValue ?? '—');
                            @endphp
                            <div class="flex items-center justify-between p-5 transition-smooth hover:bg-surface-dim/40">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-{{ $statusColor }}/10 text-{{ $statusColor }}">
                                        <span class="material-symbols-outlined text-sm">{{ $statusIcon }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-ink">{{ \Carbon\Carbon::parse($att->date)->translatedFormat('d M Y') }}</p>
                                        <p class="text-xs text-on-surface-variant">
                                            {{ $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '—' }}
                                            @if ($att->clock_out) – {{ \Carbon\Carbon::parse($att->clock_out)->format('H:i') }} @endif
                                        </p>
                                    </div>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-medium bg-{{ $statusColor }}/10 text-{{ $statusColor }}">
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
