<div>
    <x-page-shell title="{{ __('Kelola Lembur') }}" subtitle="{{ __('Pantau semua pengajuan lembur karyawan.') }}">
        <x-slot name="toolbar">
            <div class="grid gap-3 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-on-surface-variant">{{ __('Cari Karyawan') }}</label>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Nama karyawan...') }}" class="w-full rounded-xl border border-outline-variant bg-canvas py-2.5 px-4 text-sm text-ink placeholder:text-on-surface-variant/40 focus:border-ink focus:ring-1 focus:ring-ink/20">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-on-surface-variant">{{ __('Status') }}</label>
                    <select wire:model.live="statusFilter" class="w-full rounded-xl border border-outline-variant bg-canvas py-2.5 px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink/20">
                        <option value="all">{{ __('Semua') }}</option>
                        <option value="pending">{{ __('Menunggu') }}</option>
                        <option value="approved">{{ __('Disetujui') }}</option>
                        <option value="rejected">{{ __('Ditolak') }}</option>
                    </select>
                </div>
            </div>
        </x-slot>

        <div class="grid grid-cols-3 gap-3">
            <x-stat-card label="Menunggu" value="{{ $stats['pending'] }}" icon="hourglass_top" tone="warning" />
            <x-stat-card label="Disetujui" value="{{ $stats['approved'] }}" icon="check_circle" tone="success" />
            <x-stat-card label="Ditolak" value="{{ $stats['rejected'] }}" icon="cancel" tone="error" />
        </div>

        @if($overtimes->count())
        <div class="hidden lg:block overflow-hidden rounded-xl border border-outline-variant shadow-soft">
            <table class="w-full text-sm">
                <thead class="bg-surface-dim">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">{{ __('Karyawan') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">{{ __('Tanggal') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">{{ __('Jam') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">{{ __('Alasan') }}</th>
                        <th class="px-4 py-3 text-center font-semibold text-on-surface-variant">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50 bg-canvas">
                    @foreach($overtimes as $ot)
                    <tr class="hover:bg-surface-dim/30">
                        <td class="px-4 py-3">
                            <div class="font-medium text-ink">{{ $ot->employee?->full_name }}</div>
                            <div class="text-xs text-on-surface-variant">{{ $ot->employee?->position?->name }}</div>
                        </td>
                        <td class="px-4 py-3 tabular-nums">{{ $ot->date?->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $ot->start_time }} - {{ $ot->end_time }}</td>
                        <td class="px-4 py-3 max-w-xs truncate text-on-surface-variant">{{ $ot->reason }}</td>
                        <td class="px-4 py-3 text-center"><x-status-badge :tone="$ot->status->color()" :pill="true">{{ $ot->status->value }}</x-status-badge></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="border-t border-outline-variant/50 p-3">{{ $overtimes->links() }}</div>
        </div>

        <div class="lg:hidden space-y-3">
            @forelse($overtimes as $ot)
            <article class="rounded-2xl border border-outline-variant bg-surface-container-low p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-ink">{{ $ot->employee?->full_name }}</h3>
                        <p class="text-xs text-on-surface-variant">{{ $ot->date?->translatedFormat('d M Y') }} · {{ $ot->start_time }} - {{ $ot->end_time }}</p>
                    </div>
                    <x-status-badge :tone="$ot->status->color()" :pill="true">{{ $ot->status->value }}</x-status-badge>
                </div>
                <p class="mt-2 text-sm text-on-surface-variant">{{ $ot->reason }}</p>
            </article>
            @empty
            <x-empty-state title="Tidak ada lembur" description="Tidak ada pengajuan lembur untuk filter ini." framed />
            @endforelse
            {{ $overtimes->links() }}
        </div>
        @else
        <x-empty-state title="{{ __('Tidak ada lembur') }}" description="{{ __('Tidak ada pengajuan lembur untuk filter ini.') }}" framed />
        @endif
    </x-page-shell>
</div>