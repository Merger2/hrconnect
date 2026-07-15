<div>
    <x-page-shell title="{{ __('Matriks Absensi') }}" subtitle="{{ __('Pantau kehadiran seluruh karyawan.') }}">
        <x-slot name="toolbar">
            <div class="grid gap-3 md:grid-cols-5 lg:grid-cols-12">
                <div class="md:col-span-2 lg:col-span-5">
                    <label class="mb-1.5 block text-sm font-medium text-on-surface-variant">{{ __('Cari Karyawan') }}</label>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Nama atau NIP...') }}" class="w-full rounded-xl border border-outline-variant bg-canvas py-2.5 px-4 text-sm text-ink placeholder:text-on-surface-variant/40 focus:border-ink focus:ring-1 focus:ring-ink/20">
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-on-surface-variant">{{ __('Cabang') }}</label>
                    <select wire:model.live="branchId" class="w-full rounded-xl border border-outline-variant bg-canvas py-2.5 px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink/20">
                        <option value="">{{ __('Semua Cabang') }}</option>
                        @foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-on-surface-variant">{{ __('Bulan') }}</label>
                    <select wire:model.live="month" class="w-full rounded-xl border border-outline-variant bg-canvas py-2.5 px-3 text-sm">
                        @foreach ([1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $m => $label)
                        <option value="{{ $m }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-on-surface-variant">{{ __('Tahun') }}</label>
                    <select wire:model.live="year" class="w-full rounded-xl border border-outline-variant bg-canvas py-2.5 px-3 text-sm">
                        @foreach (range(now()->year - 1, now()->year) as $y)<option value="{{ $y }}">{{ $y }}</option>@endforeach
                    </select>
                </div>
            </div>
        </x-slot>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat-card label="Hadir" value="{{ $stats['present'] }}" icon="check_circle" tone="success" />
            <x-stat-card label="Terlambat" value="{{ $stats['late'] }}" icon="schedule" tone="warning" />
            <x-stat-card label="Alpa" value="{{ $stats['absent'] }}" icon="person_off" tone="error" />
            <x-stat-card label="WFA" value="{{ $stats['wfa'] }}" icon="home_work" tone="primary" />
        </div>

        @if($attendances->count())
        <div class="hidden lg:block overflow-hidden rounded-xl border border-outline-variant shadow-soft">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-dim">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">{{ __('Karyawan') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">{{ __('Tanggal') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">{{ __('Jam Masuk') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">{{ __('Jam Keluar') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-on-surface-variant">{{ __('Terlambat') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-on-surface-variant">{{ __('WFA') }}</th>
                            <th class="px-4 py-3 text-center font-semibold text-on-surface-variant">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/50 bg-canvas">
                        @foreach($attendances as $att)
                        <tr class="hover:bg-surface-dim/30">
                            <td class="px-4 py-3">
                                <div class="font-medium text-ink">{{ $att->employee?->full_name ?? '-' }}</div>
                                <div class="text-xs text-on-surface-variant">{{ $att->employee?->employee_number }} · {{ $att->employee?->position?->name }}</div>
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ $att->date?->translatedFormat('d M') }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $att->clock_in?->format('H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $att->clock_out?->format('H:i') ?? '-' }}</td>
                            <td class="px-4 py-3 text-center {{ $att->late_minutes > 0 ? 'text-warning font-semibold' : 'text-on-surface-variant' }}">{{ $att->late_minutes ?? 0 }} mnt</td>
                            <td class="px-4 py-3 text-center">
                                @if($att->is_wfa)<span class="material-symbols-outlined text-lg text-primary">check_circle</span>@else<span class="text-on-surface-variant/30">-</span>@endif
                            </td>
                            <td class="px-4 py-3 text-center"><x-status-badge :tone="$att->status->color()" :pill="true">{{ $att->status->value }}</x-status-badge></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-outline-variant/50 p-3">{{ $attendances->links() }}</div>
        </div>

        <div class="lg:hidden space-y-3">
            @forelse($attendances as $att)
            <article class="rounded-2xl border border-outline-variant bg-surface-container-low p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-ink">{{ $att->employee?->full_name }}</h3>
                        <p class="text-xs text-on-surface-variant">{{ $att->date?->translatedFormat('d M Y') }} · {{ $att->employee?->position?->name }}</p>
                    </div>
                    <x-status-badge :tone="$att->status->color()" :pill="true">{{ $att->status->value }}</x-status-badge>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <div><span class="text-on-surface-variant">Masuk</span><br><span class="font-medium tabular-nums">{{ $att->clock_in?->format('H:i') ?? '-' }}</span></div>
                    <div><span class="text-on-surface-variant">Keluar</span><br><span class="font-medium tabular-nums">{{ $att->clock_out?->format('H:i') ?? '-' }}</span></div>
                    <div><span class="text-on-surface-variant">Terlambat</span><br><span class="{{ $att->late_minutes > 0 ? 'text-warning font-semibold' : '' }}">{{ $att->late_minutes ?? 0 }} mnt</span></div>
                    <div><span class="text-on-surface-variant">WFA</span><br>@if($att->is_wfa)<span class="text-primary">Ya</span>@else<span>Tidak</span>@endif</div>
                </div>
            </article>
            @empty
            <x-empty-state title="Tidak ada data" description="Tidak ada absensi di periode ini." framed />
            @endforelse
            {{ $attendances->links() }}
        </div>
        @else
        <x-empty-state title="{{ __('Tidak ada data absensi') }}" description="{{ __('Tidak ada absensi untuk periode dan filter ini.') }}" framed />
        @endif
    </x-page-shell>
</div>