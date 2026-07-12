<x-layouts::app.sidebar :title="__('Dashboard')">
    <x-page-shell title="{{ __('Dashboard') }}" subtitle="{{ __('Ringkasan aktivitas hari ini') }}">
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

        {{-- Stat Cards --}}
        <x-app.panel>
            <dl class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-{{ min(count($stats), 4) }}">
                @foreach($stats as $stat)
                <div @class([
                    'rounded-xl border px-4 py-3 shadow-sm transition-colors',
                    'border-outline-variant/60 bg-canvas' => $stat['tone'] === 'neutral',
                    'border-outline-variant/60 bg-canvas hover:bg-surface-dim/30' => $stat['tone'] === 'primary',
                    'border-success/20 bg-success/5' => $stat['tone'] === 'success',
                    'border-warning/20 bg-warning/5' => $stat['tone'] === 'warning',
                    'border-info/20 bg-info/5' => $stat['tone'] === 'info',
                ])>
                    <dt class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-on-surface-variant">{{ $stat['label'] }}</dt>
                    <dd class="mt-1.5 text-xl font-bold text-ink">{{ $stat['value'] }}</dd>
                </div>
                @endforeach
            </dl>
        </x-app.panel>

        <livewire:quick-actions />
    </x-page-shell>
</x-layouts::app.sidebar>
