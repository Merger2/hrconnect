<x-layouts::app.sidebar :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border border-outline-variant bg-canvas p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-on-surface-variant">{{ __('Total Karyawan') }}</p>
                    <span class="material-symbols-outlined text-xl text-primary">group</span>
                </div>
                <p class="mt-2 text-2xl font-bold text-ink">{{ number_format($total_employees) }}</p>
            </div>
            <div class="rounded-xl border border-outline-variant bg-canvas p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-on-surface-variant">{{ __('Karyawan Aktif') }}</p>
                    <span class="material-symbols-outlined text-xl text-success">badge</span>
                </div>
                <p class="mt-2 text-2xl font-bold text-ink">{{ number_format($active_employees) }}</p>
            </div>
            <div class="rounded-xl border border-outline-variant bg-canvas p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-on-surface-variant">{{ __('Pending Approvals') }}</p>
                    <span class="material-symbols-outlined text-xl text-warning">pending_actions</span>
                </div>
                <p class="mt-2 text-2xl font-bold text-ink">{{ number_format($pending_approvals) }}</p>
            </div>
        </div>

        <div class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-outline-variant bg-canvas">
            <div class="text-center">
                <span class="material-symbols-outlined text-4xl text-on-surface-variant/40">monitoring</span>
                <p class="mt-2 text-sm text-on-surface-variant">{{ __('Aktivitas dan grafik akan tampil di sini') }}</p>
            </div>
        </div>
    </div>
</x-layouts::app.sidebar>
