<div>
    <x-page-shell title="{{ __('Kelola Reimbursement') }}" subtitle="{{ __('Pantau semua klaim reimbursement karyawan.') }}">
        <x-slot name="toolbar">
            <div class="grid gap-3 md:grid-cols-4">
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-on-surface-variant">{{ __('Cari Karyawan') }}</label>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Nama karyawan...') }}" class="w-full rounded-xl border border-outline-variant bg-canvas py-2.5 px-4 text-sm">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-on-surface-variant">{{ __('Status') }}</label>
                    <select wire:model.live="statusFilter" class="w-full rounded-xl border border-outline-variant bg-canvas py-2.5 px-3 text-sm">
                        <option value="all">{{ __('Semua') }}</option>
                        <option value="pending">{{ __('Menunggu') }}</option>
                        <option value="approved">{{ __('Disetujui') }}</option>
                        <option value="rejected">{{ __('Ditolak') }}</option>
                        <option value="paid">{{ __('Dibayar') }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-on-surface-variant">{{ __('Kategori') }}</label>
                    <select wire:model.live="categoryId" class="w-full rounded-xl border border-outline-variant bg-canvas py-2.5 px-3 text-sm">
                        <option value="">{{ __('Semua Kategori') }}</option>
                        @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
            </div>
        </x-slot>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat-card label="Menunggu" value="{{ $stats['pending'] }}" icon="hourglass_top" tone="warning" />
            <x-stat-card label="Disetujui" value="{{ $stats['approved'] }}" icon="check_circle" tone="success" />
            <x-stat-card label="Ditolak" value="{{ $stats['rejected'] }}" icon="cancel" tone="error" />
            <x-stat-card label="Dibayar" value="{{ $stats['paid'] }}" icon="paid" tone="primary" />
        </div>

        @if($reimbursements->count())
        <div class="hidden lg:block overflow-hidden rounded-2xl border border-outline-variant shadow-soft">
            <table class="w-full text-sm">
                <thead class="bg-surface-dim">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">{{ __('Karyawan') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">{{ __('Kategori') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-on-surface-variant">{{ __('Tanggal') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-on-surface-variant">{{ __('Nominal') }}</th>
                        <th class="px-4 py-3 text-center font-semibold text-on-surface-variant">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/50 bg-canvas">
                    @foreach($reimbursements as $r)
                    <tr class="hover:bg-surface-dim/30">
                        <td class="px-4 py-3">
                            <div class="font-medium text-ink">{{ $r->employee?->full_name }}</div>
                            <div class="text-xs text-on-surface-variant">{{ $r->employee?->position?->name }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $r->category?->name ?? '-' }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $r->expense_date?->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums">Rp {{ number_format($r->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center"><x-status-badge :tone="$r->status->color()" :pill="true">{{ $r->status->value }}</x-status-badge></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="border-t border-outline-variant/50 p-3">{{ $reimbursements->links() }}</div>
        </div>

        <div class="lg:hidden space-y-3">
            @forelse($reimbursements as $r)
            <article class="rounded-2xl border border-outline-variant bg-surface-container-low p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-ink">{{ $r->employee?->full_name }}</h3>
                        <p class="text-xs text-on-surface-variant">{{ $r->category?->name }} · {{ $r->expense_date?->translatedFormat('d M Y') }}</p>
                    </div>
                    <x-status-badge :tone="$r->status->color()" :pill="true">{{ $r->status->value }}</x-status-badge>
                </div>
                <p class="mt-2 text-base font-bold text-ink">Rp {{ number_format($r->amount, 0, ',', '.') }}</p>
            </article>
            @empty
            <x-empty-state title="Tidak ada reimbursement" description="Tidak ada klaim untuk filter ini." framed />
            @endforelse
            {{ $reimbursements->links() }}
        </div>
        @else
        <x-empty-state title="{{ __('Tidak ada reimbursement') }}" description="{{ __('Tidak ada klaim untuk filter ini.') }}" framed />
        @endif
    </x-page-shell>
</div>