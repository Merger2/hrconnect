<div>
    <x-page-shell title="{{ __('Kelola Cuti') }}" subtitle="{{ __('Pantau semua pengajuan cuti karyawan.') }}">
        <x-slot name="toolbar">
            <div class="grid gap-3 md:grid-cols-4">
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
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-on-surface-variant">{{ __('Tipe Cuti') }}</label>
                    <select wire:model.live="leaveTypeId" class="w-full rounded-xl border border-outline-variant bg-canvas py-2.5 px-3 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink/20">
                        <option value="">{{ __('Semua Tipe') }}</option>
                        @foreach($leaveTypes as $lt)<option value="{{ $lt->id }}">{{ $lt->name }}</option>@endforeach
                    </select>
                </div>
            </div>
        </x-slot>

        <div class="grid grid-cols-3 gap-3">
            <x-stat-card label="Menunggu" value="{{ $stats['pending'] }}" icon="hourglass_top" tone="warning" />
            <x-stat-card label="Disetujui" value="{{ $stats['approved'] }}" icon="check_circle" tone="success" />
            <x-stat-card label="Ditolak" value="{{ $stats['rejected'] }}" icon="cancel" tone="error" />
        </div>

        @if($leaves->count())
        <div class="hidden lg:block overflow-hidden rounded-xl border border-outline-variant shadow-soft">
            <table class="w-full text-sm">
                <thead class="bg-surface-dim">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">{{ __('Karyawan') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">{{ __('Tipe') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">{{ __('Periode') }}</th>
                        <th class="px-4 py-3 text-center font-semibold text-on-surface-variant">{{ __('Hari') }}</th>
                        <th class="px-4 py-3 text-center font-semibold text-on-surface-variant">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50 bg-canvas">
                    @foreach($leaves as $leave)
                    <tr class="hover:bg-surface-dim/30">
                        <td class="px-4 py-3">
                            <div class="font-medium text-ink">{{ $leave->employee?->full_name }}</div>
                            <div class="text-xs text-on-surface-variant">{{ $leave->employee?->position?->name }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $leave->leaveType?->name }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $leave->start_date?->translatedFormat('d M') }} — {{ $leave->end_date?->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3 text-center tabular-nums">{{ $leave->start_date?->diffInDays($leave->end_date) + 1 }}</td>
                        <td class="px-4 py-3 text-center"><x-status-badge :tone="$leave->status->color()" :pill="true">{{ $leave->status->value }}</x-status-badge></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="border-t border-outline-variant/50 p-3">{{ $leaves->links() }}</div>
        </div>

        <div class="lg:hidden space-y-3">
            @forelse($leaves as $leave)
            <article class="rounded-2xl border border-outline-variant bg-surface-container-low p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-ink">{{ $leave->employee?->full_name }}</h3>
                        <p class="text-xs text-on-surface-variant">{{ $leave->leaveType?->name }}</p>
                    </div>
                    <x-status-badge :tone="$leave->status->color()" :pill="true">{{ $leave->status->value }}</x-status-badge>
                </div>
                <p class="mt-2 text-sm text-on-surface-variant">{{ $leave->start_date?->translatedFormat('d M') }} — {{ $leave->end_date?->translatedFormat('d M Y') }} · {{ $leave->start_date?->diffInDays($leave->end_date) + 1 }} hari</p>
            </article>
            @empty
            <x-empty-state title="Tidak ada cuti" description="Tidak ada pengajuan cuti untuk filter ini." framed />
            @endforelse
            {{ $leaves->links() }}
        </div>
        @else
        <x-empty-state title="{{ __('Tidak ada cuti') }}" description="{{ __('Tidak ada pengajuan cuti untuk filter ini.') }}" framed />
        @endif
    </x-page-shell>
</div>