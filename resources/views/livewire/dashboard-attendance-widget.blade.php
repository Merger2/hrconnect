<div x-data="attendanceWidgetData()"
     x-init="init()">

    <!-- Today's Status Widget -->
    <div class="mb-6 overflow-hidden rounded-xl border border-outline-variant/50 bg-canvas shadow-soft">
        <div class="p-5">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-widest text-blue-600">{{ __('Dashboard') }}</p>
                    <h3 class="mt-1 text-lg font-semibold leading-tight tracking-tight text-ink">{{ __('Hari Ini') }}</h3>
                    <p class="mt-0.5 text-xs text-on-surface-variant" x-text="todayFormatted"></p>
                </div>

                <div x-show="todayStatus === 'complete'"
                     class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-success/30 bg-success/10 px-2.5 py-1 text-xs font-semibold text-success">
                    <span class="flex h-2 w-2 rounded-full bg-success"></span>
                    <span>{{ __('Selesai') }}</span>
                </div>
                <div x-show="todayStatus === 'incomplete'"
                     class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-info/30 bg-info/10 px-2.5 py-1 text-xs font-semibold text-info">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-info opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-info"></span>
                    </span>
                    <span>{{ __('Aktif') }}</span>
                </div>
            </div>

            {{-- Timeline: Check In / Check Out (compact) --}}
            <div class="grid grid-cols-2 gap-3">
                {{-- Check In step --}}
                <div class="relative flex min-h-[5rem] flex-col justify-between rounded-lg border p-3 transition"
                     :class="today.has_clocked_in
                         ? 'border-success/30 bg-success/[0.04]'
                         : 'border-primary/20 bg-primary/[0.04]'">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full"
                          :class="today.has_clocked_in
                              ? 'bg-success/10 text-success'
                              : 'bg-primary/10 text-primary'">
                        <span class="material-symbols-outlined text-lg" x-text="today.has_clocked_in ? 'check' : 'login'"></span>
                    </span>
                    <div class="mt-1">
                        <p class="text-sm font-semibold leading-tight text-ink">{{ __('Masuk') }}</p>
                        <p class="text-xs text-on-surface-variant" x-text="today.has_clocked_in ? '{{ __('Dicatat') }}' : '{{ __('Belum') }}'"></p>
                    </div>
                    <span class="mt-1 block font-mono text-base font-semibold tracking-tight text-ink"
                          x-text="today.has_clocked_in ? formatTimeSimple(today.attendance?.clock_in) : '--:--'"></span>
                </div>

                {{-- Check Out step --}}
                <div class="relative flex min-h-[5rem] flex-col justify-between rounded-lg border p-3 transition"
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
                        <span class="material-symbols-outlined text-lg" x-text="today.has_clocked_out ? 'check' : 'logout'"></span>
                    </span>
                    <div class="mt-1">
                        <p class="text-sm font-semibold leading-tight text-ink">{{ __('Keluar') }}</p>
                        <p class="text-xs text-on-surface-variant" x-text="today.has_clocked_out ? '{{ __('Dicatat') }}' : (today.has_clocked_in ? '{{ __('Menunggu') }}' : '{{ __('Terkunci') }}')"></p>
                    </div>
                    <span class="mt-1 block font-mono text-base font-semibold tracking-tight text-ink"
                          x-text="today.has_clocked_out ? formatTimeSimple(today.attendance?.clock_out) : '--:--'"></span>
                </div>
            </div>

            {{-- CTA Button --}}
            <div class="mt-4">
                <template x-if="!today.has_clocked_in">
                    <a href="{{ route('attendance.clock-in') }}" wire:navigate
                       class="flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-full bg-ink px-3 py-2.5 text-center text-white shadow-sm transition-all hover:bg-primary-container active:scale-[0.99]">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-white/16 ring-1 ring-white/20">
                            <span class="material-symbols-outlined text-lg">login</span>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold leading-tight">{{ __('Absen') }}</span>
                        </span>
                    </a>
                </template>
                <template x-if="today.has_clocked_in && !today.has_clocked_out">
                    <a href="{{ route('attendance.clock-in') }}" wire:navigate
                       class="flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-full bg-warning px-3 py-2.5 text-center text-white shadow-sm transition-all hover:brightness-90 active:scale-[0.99]">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-white/16 ring-1 ring-white/20">
                            <span class="material-symbols-outlined text-lg">logout</span>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold leading-tight">{{ __('Keluar') }}</span>
                        </span>
                    </a>
                </template>
                <template x-if="today.has_clocked_in && today.has_clocked_out">
                    <div class="flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-full bg-success/10 px-3 py-2.5 text-center text-success ring-1 ring-success/20">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-success/20">
                            <span class="material-symbols-outlined text-lg">check_circle</span>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold leading-tight">{{ __('Selesai') }}</span>
                            <span class="text-xs text-success/70">{{ __('Hari ini lengkap') }}</span>
                        </span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Summary Stats (compact) --}}
    <div class="mb-4 grid grid-cols-3 gap-2" x-show="!loading">
        <div class="flex flex-col items-center gap-1.5 rounded-lg border border-outline-variant/50 bg-canvas p-2 shadow-soft">
            <span class="material-symbols-outlined text-base text-success">check_circle</span>
            <dd class="text-sm font-bold text-ink" x-text="summary.days_worked">0</dd>
            <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Hadir') }}</dt>
        </div>
        <div class="flex flex-col items-center gap-1.5 rounded-lg border border-warning/30 bg-canvas p-2 shadow-soft">
            <span class="material-symbols-outlined text-base text-warning">error_outline</span>
            <dd class="text-sm font-bold text-ink" x-text="summary.late_count">0</dd>
            <dt class="text-xs font-semibold uppercase tracking-wider text-warning">{{ __('Terlambat') }}</dt>
        </div>
        <div class="flex flex-col items-center gap-1.5 rounded-lg border border-error/30 bg-canvas p-2 shadow-soft">
            <span class="material-symbols-outlined text-base text-error">cancel</span>
            <dd class="text-sm font-bold text-ink" x-text="summary.absent_count">0</dd>
            <dt class="text-xs font-semibold uppercase tracking-wider text-error">{{ __('Alpa') }}</dt>
        </div>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="flex items-center justify-center gap-2 py-10 text-sm text-on-surface-variant">
        <span class="material-symbols-outlined animate-spin text-base">progress_activity</span>
        {{ __('Memuat...') }}
    </div>

    {{-- Quick Period Switcher --}}
    <div class="mb-3 flex items-center justify-between gap-2">
        <p class="text-sm font-semibold text-ink">{{ __('Riwayat Absensi') }}</p>
        <div class="flex items-center gap-1.5">
            <button @click="setPeriod(currentPeriod())"
                    :class="period === currentPeriod() ? 'bg-ink text-white shadow-sm' : 'border border-outline-variant bg-canvas text-ink hover:bg-surface-container-low'"
                    class="rounded-lg px-2.5 py-1.5 text-xs font-medium transition-all">
                {{ __('Bulan Ini') }}
            </button>
            <button @click="setPeriod(lastMonthPeriod())"
                    :class="period === lastMonthPeriod() ? 'bg-ink text-white shadow-sm' : 'border border-outline-variant bg-canvas text-ink hover:bg-surface-container-low'"
                    class="rounded-lg px-2.5 py-1.5 text-xs font-medium transition-all">
                {{ __('Bulan Lalu') }}
            </button>
        </div>
    </div>

    {{-- Records --}}
    <div x-show="!loading" class="overflow-hidden rounded-lg border border-outline-variant/50 bg-canvas">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap text-left text-sm">
                <thead class="bg-surface-dim text-on-surface-variant">
                    <tr>
                        <th scope="col" class="px-3 py-2 font-medium">{{ __('Tanggal') }}</th>
                        <th scope="col" class="px-3 py-2 font-medium">{{ __('Masuk') }}</th>
                        <th scope="col" class="px-3 py-2 font-medium">{{ __('Keluar') }}</th>
                        <th scope="col" class="px-3 py-2 font-medium">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10" x-show="records.length > 0">
                    <template x-for="r in records.slice(0, 7)" :key="r.id">
                        <tr class="transition-colors hover:bg-surface-dim">
                            <td class="px-3 py-2 font-medium text-ink" x-text="formatDate(r.date)"></td>
                            <td class="px-3 py-2 text-ink" x-text="formatTime(r.clock_in)"></td>
                            <td class="px-3 py-2 text-ink" x-text="formatTime(r.clock_out)"></td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                      :class="statusClasses(r.status)"
                                      x-text="statusLabel(r.status)"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <template x-if="records.length === 0">
            <div class="flex flex-col items-center gap-2 py-8">
                <span class="material-symbols-outlined text-3xl text-on-surface-variant/30">schedule</span>
                <p class="text-sm font-medium text-ink">{{ __('Belum ada data absensi') }}</p>
                <p class="text-xs text-on-surface-variant">{{ __('Absen untuk mulai mencatat') }}</p>
            </div>
        </template>
    </div>

    <a href="{{ route('attendance.index') }}" wire:navigate
       class="mt-3 flex items-center justify-center gap-1.5 rounded-lg border border-outline-variant bg-canvas py-2 text-sm font-medium text-ink transition-colors hover:bg-surface-container-low">
        <span class="material-symbols-outlined text-base">history</span>
        {{ __('Lihat Semua Riwayat') }}
    </a>

</div>

<script>
function attendanceWidgetData() {
    return {
        today: { has_clocked_in: false, has_clocked_out: false, attendance: null },
        todayStatus: 'incomplete',
        todayFormatted: '',
        period: '',
        loading: true,
        summary: { days_worked: 0, late_count: 0, absent_count: 0 },
        records: [],

        init() {
            this.todayFormatted = new Date().toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
            this.period = this.currentPeriod();
            this.loadData();
        },

        async loadData() {
            await this.fetchToday();
            await this.fetchAttendance();
        },

        async fetchToday() {
            this.loading = true;
            try {
                const res = await fetch('/api/v1/attendance/today', {
                    headers: this.apiHeaders()
                });
                if (res.ok) {
                    const json = await res.json();
                    if (json.status === 'success') {
                        this.today = json.data;
                        this.todayStatus = (this.today.has_clocked_in && this.today.has_clocked_out) ? 'complete' : 'incomplete';
                    }
                }
            } catch {
                console.error('Failed to fetch today\'s attendance');
            }
            finally { this.loading = false; }
        },

        async fetchAttendance() {
            this.loading = true;
            try {
                const res = await fetch(`/api/v1/attendance?period=${this.period}&per_page=30`, {
                    headers: this.apiHeaders()
                });
                if (res.ok) {
                    const json = await res.json();
                    if (json.status === 'success') {
                        this.records = json.data;
                        this.calcSummary();
                    }
                }
            } catch {
                console.error('Failed to fetch attendance');
            }
            finally { this.loading = false; }
        },

        calcSummary() {
            const present = this.records.filter(r => ['present', 'late', 'wfa'].includes(r.status)).length;
            const late = this.records.filter(r => r.status === 'late').length;
            const absent = this.records.filter(r => r.status === 'absent').length;
            this.summary = { days_worked: present, late_count: late, absent_count: absent };
        },

        setPeriod(period) {
            this.period = period;
            this.fetchAttendance();
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

        formatTimeSimple(timeStr) {
            if (!timeStr) return '--:--';
            const d = new Date(timeStr);
            return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        formatTime(timeStr) {
            if (!timeStr) return '--:--';
            const d = new Date(timeStr);
            return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
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
            const labels = { present: 'Hadir', late: 'Terlambat', absent: 'Absen', wfa: 'WFA' };
            return labels[status] || status;
        },

        apiHeaders() {
            return {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            };
        }
    };
}
</script>