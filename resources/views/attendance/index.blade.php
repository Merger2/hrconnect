<x-layouts::app.sidebar>
    <div x-data="attendanceIndex()">
        <x-page-shell title="{{ __('Absensi') }}" subtitle="{{ __('Riwayat kehadiran Anda') }}">
        <div x-show="!loading" class="overflow-hidden rounded-2xl border border-outline-variant bg-canvas shadow-soft">

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
                     class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-primary/30 bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-primary"></span>
                    </span>
                    <span>{{ __('Live') }}</span>
                </div>
            </div>

            {{-- Worktime info pill --}}
            <div class="mx-4 mt-3 flex items-center gap-2.5 rounded-full border border-outline-variant bg-surface-container-low px-3 py-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-primary/10 text-primary">
                    <span class="material-symbols-outlined text-lg">schedule</span>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold leading-tight text-ink">{{ __('Working Hours') }}</p>
                    <p class="truncate text-xs leading-4 text-on-surface-variant">{{ __('08:00 - 17:00') }} <span class="text-on-surface-variant/40">•</span> {{ __('Flexible') }}</p>
                </div>
            </div>

            {{-- Timeline: Check In / Check Out --}}
            <div class="mx-4 mt-3 grid grid-cols-2 gap-2.5">
                {{-- Check In step --}}
                <div class="relative flex min-h-[5.5rem] flex-col justify-between rounded-2xl border p-3 transition"
                     :class="today.has_clocked_in
                         ? 'border-success/30 bg-success/10'
                         : 'border-primary/30 bg-primary/10'">
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
                <div class="relative flex min-h-[5.5rem] flex-col justify-between rounded-2xl border p-3 transition"
                     :class="today.has_clocked_out
                         ? 'border-success/30 bg-success/10'
                         : today.has_clocked_in
                             ? 'border-warning/30 bg-warning/10'
                             : 'border-outline-variant opacity-60'">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full"
                          :class="today.has_clocked_out
                              ? 'bg-success/10 text-success'
                              : today.has_clocked_in
                                  ? 'bg-warning/10 text-warning'
                                  : 'bg-surface-container-high text-on-surface-variant'">
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
                       class="flex min-h-[3.5rem] w-full items-center justify-center gap-3 rounded-2xl bg-primary px-4 py-3 text-center text-on-primary shadow-soft transition-all hover:bg-primary-deep active:scale-[0.99]">
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
                       class="flex min-h-[3.5rem] w-full items-center justify-center gap-3 rounded-2xl bg-primary px-4 py-3 text-center text-on-primary shadow-soft transition-all hover:bg-primary-deep active:scale-[0.99]">
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
                    <div class="flex min-h-[3.5rem] w-full items-center justify-center gap-3 rounded-2xl bg-success/10 px-4 py-3 text-center text-success ring-1 ring-success/20">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-success/10">
                            <span class="material-symbols-outlined text-lg">check_circle</span>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-base font-semibold leading-tight">{{ __('Attendance Complete') }}</span>
                            <span class="text-xs text-success/80">{{ __('Good job! Today is done.') }}</span>
                        </span>
                    </div>
                </template>
            </div>
        </div>

        {{-- History Section --}}
        <div class="mb-4 flex items-center gap-2">
            <div class="ess-segment">
                <button @click="period = currentPeriod(); fetchAttendance()"
                        :class="period === currentPeriod() ? 'ess-segment__btn ess-segment__btn--active' : 'ess-segment__btn'">
                    {{ __('This Month') }}
                </button>
                <button @click="period = lastMonthPeriod(); fetchAttendance()"
                        :class="period === lastMonthPeriod() ? 'ess-segment__btn ess-segment__btn--active' : 'ess-segment__btn'">
                    {{ __('Last Month') }}
                </button>
            </div>
        </div>

        {{-- Summary stats --}}
        <div class="mb-5 grid grid-cols-3 gap-3" x-show="!loading">
            <div class="ess-stat items-center text-center">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-success/10 text-success">
                    <span class="material-symbols-outlined text-xl">check_circle</span>
                </span>
                <dd class="text-lg font-bold text-ink" x-text="summary.days_worked">0</dd>
                <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Present') }}</dt>
            </div>
            <div class="ess-stat items-center text-center">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-warning/10 text-warning">
                    <span class="material-symbols-outlined text-xl">error_outline</span>
                </span>
                <dd class="text-lg font-bold text-ink" x-text="summary.late_count">0</dd>
                <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Late') }}</dt>
            </div>
            <div class="ess-stat items-center text-center">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-error/10 text-error">
                    <span class="material-symbols-outlined text-xl">cancel</span>
                </span>
                <dd class="text-lg font-bold text-ink" x-text="summary.absent_count">0</dd>
                <dt class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Absent') }}</dt>
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
                        <thead class="bg-surface-container-low text-on-surface-variant">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Date') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Clock In') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Clock Out') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/50">
                            <template x-for="r in records" :key="r.id">
                                <tr class="transition-colors hover:bg-surface-container-low">
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
                                            <span class="material-symbols-outlined text-3xl text-on-surface-variant/40">schedule</span>
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
                    <div class="overflow-hidden rounded-2xl border border-outline-variant bg-canvas shadow-soft"
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
                                <div class="flex items-center gap-2.5 rounded-xl bg-surface-container-low px-3 py-2.5">
                                    <span class="material-symbols-outlined text-lg text-success">login</span>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('In') }}</p>
                                        <p class="text-sm font-medium text-ink" x-text="formatTime(r.clock_in) || '--:--'"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5 rounded-xl bg-surface-container-low px-3 py-2.5">
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
                    <div class="flex flex-col items-center gap-3 rounded-2xl border-2 border-dashed border-outline-variant bg-canvas py-16">
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant/40">schedule</span>
                        <p class="text-sm font-medium text-ink">{{ __('No attendance data yet') }}</p>
                        <p class="text-xs text-on-surface-variant">{{ __('Clock in to start recording') }}</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-page-shell>
</x-layouts::app.sidebar>
