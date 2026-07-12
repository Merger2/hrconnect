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

        $toneStyles = [
            'primary' => 'bg-blue-600/10 text-blue-600',
            'success' => 'bg-success/10 text-success',
            'warning' => 'bg-warning/10 text-warning',
            'info'    => 'bg-info/10 text-info',
            'neutral' => 'bg-cloud text-graphite',
        ];
        $borderStyles = [
            'primary' => 'hover:border-blue-600',
            'success' => 'hover:border-success',
            'warning' => 'hover:border-warning',
            'info'    => 'hover:border-info',
            'neutral' => 'hover:border-steel',
        ];
    @endphp

    <div class="flex flex-col gap-6">
        <!-- Gradient Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 to-blue-800 px-6 py-8 shadow-lg">
            <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
            <div class="absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-white/5 blur-2xl"></div>
            <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-white sm:text-3xl">{{ __('Selamat datang kembali') }}</h1>
                    <p class="mt-1 text-sm text-white/80">{{ __('Berikut ringkasan aktivitas Anda hari ini') }}</p>
                </div>
                <a href="{{ route('attendance.clock-in') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur transition-colors hover:bg-white/25">
                    <span class="material-symbols-outlined text-xl">login</span>
                    {{ __('Absen Masuk') }}
                </a>
            </div>
        </div>

        <!-- Stat Cards -->
        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($stats as $stat)
            <div @class([
                'group relative overflow-hidden rounded-xl border border-outline-variant/60 bg-canvas p-5 shadow-soft transition-all duration-300 hover:-translate-y-1 hover:shadow-lg',
                $borderStyles[$stat['tone']] ?? 'hover:border-steel',
            ])>
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-400 to-blue-600 scale-x-0 transition-transform duration-300 group-hover:scale-x-100"></div>
                <div class="flex items-start justify-between">
                    <dt class="text-[0.7rem] font-bold uppercase tracking-[0.12em] text-on-surface-variant">{{ $stat['label'] }}</dt>
                    <div @class(['flex h-11 w-11 items-center justify-center rounded-xl', $toneStyles[$stat['tone']] ?? 'bg-cloud text-graphite'])>
                        <span class="material-symbols-outlined text-xl">{{ $stat['icon'] }}</span>
                    </div>
                </div>
                <dd class="mt-3 text-3xl font-bold tracking-tight text-ink">{{ $stat['value'] }}</dd>
            </div>
            @endforeach
        </dl>

        <!-- Quick Actions -->
        <livewire:quick-actions />
    </div>
</x-layouts::app.sidebar>
