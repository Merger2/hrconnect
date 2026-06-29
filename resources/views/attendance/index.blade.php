<x-layouts::app.sidebar>
    <div x-data="attendanceIndex()">
        <div class="mb-5">
            <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">{{ __('Attendance') }}</h1>
            <p class="mt-0.5 text-sm text-on-surface-variant">{{ __('Your attendance records') }}</p>
        </div>

        <div x-show="!loading" class="mb-6 overflow-hidden rounded-xl border border-outline-variant/60 bg-canvas shadow-sm">

            <div class="relative flex items-start justify-between gap-3 p-4 pb-0">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-widest text-primary">{{ __('Attendance') }}</p>
                    <h2 class="mt-1 text-lg font-semibold leading-tight tracking-tight text-ink sm:text-xl">{{ __('Today') }}</h2>
                    <p class="mt-0.5 text-xs text-on-surface-variant sm:text-sm" x-text="todayFormatted"></p>
                </div>

                <div x-show="todayStatus === 'complete'"
                     class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-success/30 bg-success/10 px-2.5 py-1 text-xs font-semibold text-success">
                    <span class="flex h-2 w-2 rounded-full bg-success"></span>
                    <span>{{ __('Done') }}</span>
                </div>
                <div x-show="todayStatus === 'incomplete'"
                     class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-info/30 bg-info/10 px-2.5 py-1 text-xs font-semibold text-info">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-info opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-info"></span>
                    </span>
                    <span>{{ __('Live') }}</span>
                </div>
            </div>

            {{-- Worktime info pill --}}
            <div class="mx-4 mt-3 flex items-center gap-2.5 rounded-full border border-outline-variant/30 bg-surface-container-low px-3 py-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-primary/10 text-primary">
                    <span class="material-symbols-outlined text-lg">schedule</span>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold leading-tight text-ink">{{ __('Working Hours') }}</p>
                    <p class="truncate text-xs leading-4 text-on-surface-variant">{{ __('08:00 - 17:00') }} <span class="text-outline-variant">•</span> {{ __('Flexible') }}</p>
                </div>
            </div>

            {{-- Timeline: Check In / Check Out --}}
            <div class="mx-4 mt-3 grid grid-cols-2 gap-2.5">
                {{-- Check In step --}}
                <div class="relative flex min-h-[5.5rem] flex-col justify-between rounded-xl border p-3 transition"
                     :class="today.has_clocked_in
                         ? 'border-success/30 bg-success/[0.04]'
                         : 'border-primary/20 bg-primary/[0.04]'">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full"
                          :class="today.has_clocked_in
                              ? 'bg-success/10 text-success'
                              : 'bg-primary/10 text-primary'">
                        <span class="material-symbols-outlined text-lg"
                              x-text="today.has_clocked_in ? 'check' : 'login'"></span>
                    </span>
                    <div class="mt-2">
                        <p class="text-sm font-semibold leading-tight text-ink">{{ __('Check In') }}</p>
                        <p class="text-xs text-on-surface-variant" x-text="today.has_clocked_in ? '{{ __('Recorded') }}' : '{{ __('Not yet') }}'"></p>
                    </div>
                    <span class="mt-1 block font-mono text-xl font-semibold tracking-tight text-ink"
                          x-text="today.has_clocked_in ? formatTimeSimple(today.attendance?.clock_in) : '--:--'"></span>
                </div>

                {{-- Check Out step --}}
                <div class="relative flex min-h-[5.5rem] flex-col justify-between rounded-xl border p-3 transition"
                     :class="today.has_clocked_out
                         ? 'border-success/30 bg-success/[0.04]'
                         : today.has_clocked_in
                             ? 'border-warning/30 bg-warning/[0.04]'
                             : 'border-outline-variant/30 opacity-60'">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full"
                          :class="today.has_clocked_out
                              ? 'bg-success/10 text-success'
                              : today.has_clocked_in
                                  ? 'bg-warning/10 text-warning'
                                  : 'bg-surface-dim text-on-surface-variant'">
                        <span class="material-symbols-outlined text-lg"
                              x-text="today.has_clocked_out ? 'check' : 'logout'"></span>
                    </span>
                    <div class="mt-2">
                        <p class="text-sm font-semibold leading-tight text-ink">{{ __('Check Out') }}</p>
                        <p class="text-xs text-on-surface-variant" x-text="today.has_clocked_out ? '{{ __('Recorded') }}' : (today.has_clocked_in ? '{{ __('Pending') }}' : '{{ __('Locked') }}')"></p>
                    </div>
                    <span class="mt-1 block font-mono text-xl font-semibold tracking-tight text-ink"
                          x-text="today.has_clocked_out ? formatTimeSimple(today.attendance?.clock_out) : '--:--'"></span>
                </div>
            </div>

            {{-- CTA Button — inside the card, full-width pill (PasPapan pattern) --}}
            <div class="p-4">
                <template x-if="!today.has_clocked_in">
                    <a href="{{ route('attendance.clock-in') }}" wire:navigate
                       class="flex min-h-[3.5rem] w-full items-center justify-center gap-3 rounded-full bg-ink px-4 py-3 text-center text-white shadow-sm transition-all hover:bg-[#1f1f1f] active:scale-[0.99]">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white/16 ring-1 ring-white/20">
                            <span class="material-symbols-outlined text-lg">login</span>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-base font-semibold leading-tight">{{ __('Clock In') }}</span>
                            <span class="sr-only">{{ __('Ready to start your day?') }}</span>
                        </span>
                    </a>
                </template>
                <template x-if="today.has_clocked_in && !today.has_clocked_out">
                    <a href="{{ route('attendance.clock-in') }}" wire:navigate
                       class="flex min-h-[3.5rem] w-full items-center justify-center gap-3 rounded-full bg-warning px-4 py-3 text-center text-white shadow-sm transition-all hover:brightness-90 active:scale-[0.99]">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white/16 ring-1 ring-white/20">
                            <span class="material-symbols-outlined text-lg">logout</span>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-base font-semibold leading-tight">{{ __('Clock Out') }}</span>
                            <span class="sr-only">{{ __('Complete your shift') }}</span>
                        </span>
                    </a>
                </template>
                <template x-if="today.has_clocked_in && today.has_clocked_out">
                    <div class="flex min-h-[3.5rem] w-full items-center justify-center gap-3 rounded-full bg-success/10 px-4 py-3 text-center text-success ring-1 ring-success/20">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-success/20">
                            <span class="material-symbols-outlined text-lg">check_circle</span>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-base font-semibold leading-tight">{{ __('Attendance Complete') }}</span>
                            <span class="text-xs text-success/70">{{ __('Good job! Today is done.') }}</span>
                        </span>
                    </div>
                </template>
            </div>
        </div>

        {{-- History Section --}}
        <div class="mb-4 flex items-center gap-2">
            <button @click="period = currentPeriod(); fetchAttendance()"
                    :class="period === currentPeriod() ? 'bg-ink text-white shadow-sm' : 'border border-outline-variant bg-canvas text-ink hover:bg-surface-container-low'"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition-all">
                {{ __('This Month') }}
            </button>
            <button @click="period = lastMonthPeriod(); fetchAttendance()"
                    :class="period === lastMonthPeriod() ? 'bg-ink text-white shadow-sm' : 'border border-outline-variant bg-canvas text-ink hover:bg-surface-container-low'"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition-all">
                {{ __('Last Month') }}
            </button>
        </div>

        {{-- Summary stats --}}
        <div class="mb-5 grid grid-cols-3 gap-3" x-show="!loading">
            <div class="flex flex-col items-center gap-1.5 rounded-xl border border-outline-variant/60 bg-canvas p-3 shadow-sm">
                <span class="material-symbols-outlined text-xl text-success">check_circle</span>
                <dd class="text-lg font-bold text-ink" x-text="summary.days_worked">0</dd>
                <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Present') }}</dt>
            </div>
            <div class="flex flex-col items-center gap-1.5 rounded-xl border border-warning/30 bg-canvas p-3 shadow-sm">
                <span class="material-symbols-outlined text-xl text-warning">error_outline</span>
                <dd class="text-lg font-bold text-ink" x-text="summary.late_count">0</dd>
                <dt class="text-xs font-semibold uppercase tracking-wider text-warning">{{ __('Late') }}</dt>
            </div>
            <div class="flex flex-col items-center gap-1.5 rounded-xl border border-error/30 bg-canvas p-3 shadow-sm">
                <span class="material-symbols-outlined text-xl text-error">cancel</span>
                <dd class="text-lg font-bold text-ink" x-text="summary.absent_count">0</dd>
                <dt class="text-xs font-semibold uppercase tracking-wider text-error">{{ __('Absent') }}</dt>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="flex items-center justify-center gap-2 py-20 text-sm text-on-surface-variant">
            <span class="material-symbols-outlined animate-spin text-lg">progress_activity</span>
            {{ __('Memuat...') }}
        </div>

        {{-- Records --}}
        <div x-show="!loading">
            {{-- Desktop Table --}}
            <x-app.panel class="hidden lg:block">
                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap text-left text-sm">
                        <thead class="bg-surface-dim text-on-surface-variant">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Date') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Clock In') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Clock Out') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <template x-for="r in records" :key="r.id">
                                <tr class="transition-colors hover:bg-surface-dim">
                                    <td class="px-4 py-3 font-medium text-ink" x-text="formatDate(r.date)"></td>
                                    <td class="px-4 py-3 text-ink" x-text="formatTime(r.clock_in)"></td>
                                    <td class="px-4 py-3 text-ink" x-text="formatTime(r.clock_out)"></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                              :class="statusClasses(r.status)"
                                              x-text="statusLabel(r.status)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-xs text-on-surface-variant"
                                        x-text="r.late_minutes ? r.late_minutes + 'm late' : ''"></td>
                                </tr>
                            </template>
                            <template x-if="records.length === 0">
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="material-symbols-outlined text-3xl text-on-surface-variant/30">schedule</span>
                                            <p class="text-sm font-medium text-ink">{{ __('No attendance data yet') }}</p>
                                            <p class="text-xs text-on-surface-variant">{{ __('Clock in to start recording') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </x-app.panel>

            {{-- Mobile Cards --}}
            <div class="space-y-2 lg:hidden">
                <template x-for="r in records" :key="r.id">
                    <div class="overflow-hidden rounded-xl border shadow-sm"
                         :class="statusBorder(r.status)">
                        <div class="bg-canvas p-4">
                            <div class="mb-3 flex items-start justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-lg text-on-surface-variant">calendar_today</span>
                                    <span class="text-sm font-semibold text-ink" x-text="formatDate(r.date)"></span>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                      :class="statusClasses(r.status)"
                                      x-text="statusLabel(r.status)"></span>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex items-center gap-2.5 rounded-lg bg-surface-container-low px-3 py-2.5">
                                    <span class="material-symbols-outlined text-lg text-success">login</span>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('In') }}</p>
                                        <p class="text-sm font-medium text-ink" x-text="formatTime(r.clock_in) || '--:--'"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5 rounded-lg bg-surface-container-low px-3 py-2.5">
                                    <span class="material-symbols-outlined text-lg text-error">logout</span>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Out') }}</p>
                                        <p class="text-sm font-medium text-ink" x-text="formatTime(r.clock_out) || '--:--'"></p>
                                    </div>
                                </div>
                            </div>
                            <p x-show="r.late_minutes" class="mt-2 flex items-center gap-1 text-xs font-medium text-warning">
                                <span class="material-symbols-outlined text-sm">schedule</span>
                                <span x-text="r.late_minutes + ' menit terlambat'"></span>
                            </p>
                        </div>
                    </div>
                </template>
                <template x-if="records.length === 0">
                    <div class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-outline-variant bg-canvas py-16">
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant/30">schedule</span>
                        <p class="text-sm font-medium text-ink">{{ __('No attendance data yet') }}</p>
                        <p class="text-xs text-on-surface-variant">{{ __('Clock in to start recording') }}</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('attendanceIndex', () => ({
                records: [],
                period: '',
                loading: true,
                today: { has_clocked_in: false, has_clocked_out: false, attendance: null },
                todayStatus: 'incomplete',
                todayFormatted: '',
                summary: { days_worked: 0, late_count: 0, absent_count: 0 },

                init() {
                    this.todayFormatted = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                    this.period = this.currentPeriod();
                    Promise.all([this.fetchToday(), this.fetchAttendance()]);
                },

                currentPeriod() {
                    const d = new Date();
                    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
                },

                lastMonthPeriod() {
                    const d = new Date();
                    d.setMonth(d.getMonth() - 1);
                    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
                },

                async fetchToday() {
                    try {
                        const res = await fetch('/api/v1/attendance/today', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            this.today = json.data;
                            if (this.today.has_clocked_in && this.today.has_clocked_out) {
                                this.todayStatus = 'complete';
                            } else {
                                this.todayStatus = 'incomplete';
                            }
                        }
                    } catch { /* silent */ }
                },

                async fetchAttendance() {
                    this.loading = true;
                    try {
                        const res = await fetch(`/api/v1/attendance?period=${this.period}&per_page=50`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            this.records = json.data;
                            this.calcSummary();
                        }
                    } catch { /* silent */ }
                    finally { this.loading = false; }
                },

                calcSummary() {
                    const present = this.records.filter(r => r.status === 'present' || r.status === 'late' || r.status === 'wfa').length;
                    const late = this.records.filter(r => r.status === 'late').length;
                    const absent = this.records.filter(r => r.status === 'absent').length;
                    this.summary = { days_worked: present, late_count: late, absent_count: absent };
                },

                statusBorder(status) {
                    const map = {
                        present: 'border-l-4 border-l-success',
                        late: 'border-l-4 border-l-warning',
                        absent: 'border-l-4 border-l-error',
                        wfa: 'border-l-4 border-l-info',
                    };
                    return map[status] || 'border-l-4 border-l-outline-variant';
                },

                statusClasses(status) {
                    const map = {
                        present: 'bg-success/10 text-success ring-success/30',
                        late: 'bg-warning/10 text-warning ring-warning/30',
                        absent: 'bg-error/10 text-error ring-error/30',
                        wfa: 'bg-info/10 text-info ring-info/30',
                    };
                    return map[status] || '';
                },

                statusLabel(status) {
                    const labels = {
                        present: '{{ __('Present') }}',
                        late: '{{ __('Late') }}',
                        absent: '{{ __('Absent') }}',
                        wfa: 'WFA',
                    };
                    return labels[status] || status;
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr + 'T00:00:00');
                    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                },

                formatTime(timeStr) {
                    if (!timeStr) return null;
                    const d = new Date(timeStr);
                    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                },

                formatTimeSimple(timeStr) {
                    if (!timeStr) return null;
                    const d = new Date(timeStr);
                    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                },
            }));
        });
    </script>
</x-layouts::app.sidebar>
