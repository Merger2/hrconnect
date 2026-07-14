<x-layouts::app.sidebar :title="__('Dashboard')">
    @php
        $role = $role ?? 'employee';
        $stats = match($role) {
            'hr' => [
                ['label' => __('Total Karyawan'), 'value' => number_format($total_employees ?? 0), 'icon' => 'group', 'tone' => 'primary'],
                ['label' => __('Aktif'), 'value' => number_format($active_employees ?? 0), 'icon' => 'badge', 'tone' => 'success'],
                ['label' => __('Persetujuan Tertunda'), 'value' => number_format($pending_approvals ?? 0), 'icon' => 'pending_actions', 'tone' => 'warning'],
                ['label' => __('Absen Hari Ini'), 'value' => number_format($hadir_hari_ini ?? 0), 'icon' => 'today', 'tone' => 'info'],
            ],
            'manager' => [
                ['label' => __('Anggota Tim'), 'value' => number_format($team_size ?? 0).' '.__('orang'), 'icon' => 'group', 'tone' => 'primary'],
                ['label' => __('Persetujuan Tim'), 'value' => number_format($team_pending ?? 0), 'icon' => 'pending_actions', 'tone' => 'warning'],
                ['label' => __('Cuti Saya'), 'value' => $cuti_anda ?? 0, 'icon' => 'calendar_month', 'tone' => 'warning'],
            ],
            'finance' => [
                ['label' => __('Klaim Tertunda'), 'value' => number_format($pending_reimbursements ?? 0), 'icon' => 'receipt_long', 'tone' => 'warning'],
                ['label' => __('Payroll Tertunda'), 'value' => number_format($pending_payrolls ?? 0), 'icon' => 'payments', 'tone' => 'warning'],
                ['label' => __('Absen Hari Ini'), 'value' => ($hadir_hari_ini ?? false) ? __('Hadir') : __('Belum Absen'), 'icon' => 'fact_check', 'tone' => ($hadir_hari_ini ?? false) ? 'success' : 'neutral'],
            ],
            default => [
                ['label' => __('Absen Hari Ini'), 'value' => ($hadir_hari_ini ?? false) ? __('Hadir') : __('Belum Absen'), 'icon' => 'fact_check', 'tone' => ($hadir_hari_ini ?? false) ? 'success' : 'neutral'],
                ['label' => __('Cuti Menunggu'), 'value' => $cuti_anda ?? 0, 'icon' => 'calendar_month', 'tone' => 'warning'],
                ['label' => __('Klaim Menunggu'), 'value' => $pengajuan_anda ?? 0, 'icon' => 'wallet', 'tone' => 'warning'],
            ],
        };
    @endphp

    <div class="space-y-6">
        <!-- Welcome Banner -->
        <div class="relative overflow-hidden rounded-2xl border border-outline-variant/60 bg-canvas p-6 shadow-soft">
            <div class="pointer-events-none absolute -right-10 -top-10 opacity-[0.06]">
                <span class="material-symbols-outlined text-[10rem]">dashboard</span>
            </div>
            <div class="relative flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-primary">{{ __('Dashboard') }}</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ __('Selamat datang kembali') }}</h1>
                    <p class="mt-1 text-sm text-on-surface-variant">{{ __('Berikut ringkasan aktivitas Anda hari ini.') }}</p>
                </div>
                <div class="hidden shrink-0 rounded-full bg-primary/10 px-4 py-2 text-sm font-semibold text-primary sm:block">
                    {{ now()->translatedFormat('l, d M Y') }}
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($stats as $stat)
                <div class="stat-card flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wider text-on-surface-variant">{{ $stat['label'] }}</p>
                        <span class="stat-icon stat-icon-blue">
                            <span class="material-symbols-outlined text-xl">{{ $stat['icon'] }}</span>
                        </span>
                    </div>
                    <p class="text-3xl font-bold tracking-tight text-ink">{{ $stat['value'] }}</p>
                </div>
            @endforeach
        </div>

        <!-- Quick Actions -->
        <div class="space-y-4">
            <h2 class="text-lg font-semibold tracking-tight text-ink">{{ __('Akses Cepat') }}</h2>
            <livewire:quick-actions />
        </div>
    </div>
</x-layouts::app.sidebar>