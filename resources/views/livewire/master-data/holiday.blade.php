<div>
    <x-page-shell title="{{ __('Hari Libur') }}" subtitle="{{ __('Kelola hari libur nasional dan perusahaan.') }}">
        <div class="flex flex-wrap items-center gap-3">
            @if($this->canManage())
            <x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Hari Libur') }}</x-button>
            @endif
            <div class="min-w-[200px] flex-1">
                <input type="search" wire:model.live.debounce.250ms="search" placeholder="{{ __('Cari hari libur...') }}" class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-4 text-sm text-ink outline-none" />
            </div>
            <select wire:model.live="yearFilter" class="h-10 rounded-xl border border-outline-variant bg-canvas px-3 text-sm">
                @foreach($years as $y)
                <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
                <option value="{{ now()->year }}">{{ now()->year }}</option>
            </select>
        </div>

        @if($holidays->count())
        <x-simple-table :headers="[__('Tanggal'), __('Nama'), __('Status'), __('Aksi')]">
            @foreach($holidays as $holiday)
            <tr>
                <td class="px-4 py-3 text-sm tabular-nums">{{ $holiday->date?->translatedFormat('d M Y') }}</td>
                <td class="px-4 py-3 font-medium text-ink">{{ $holiday->name }}</td>
                <td class="px-4 py-3">
                    @if($holiday->is_active)
                    <x-status-badge tone="success" :pill="true">{{ __('Aktif') }}</x-status-badge>
                    @else
                    <x-status-badge tone="neutral" :pill="true">{{ __('Nonaktif') }}</x-status-badge>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($this->canManage())
                    <div class="flex gap-1">
                        <x-button variant="ghost" size="sm" wire:click="edit({{ $holiday->id }})">{{ __('Edit') }}</x-button>
                        <x-button variant="ghost" size="sm" wire:click="confirmDeletion({{ $holiday->id }})">{{ __('Hapus') }}</x-button>
                    </div>
                    @endif
                </td>
            </tr>
            @endforeach
        </x-simple-table>
        <x-pagination :paginator="$holidays" />
        @else
        <x-empty-state :title="filled($search) ? __('Tidak ada hari libur ditemukan') : __('Belum ada hari libur tahun ' . $yearFilter)" :description="filled($search) ? __('Coba ubah kata kunci.') : __('Tambahkan hari libur untuk tahun ini.')" />
        @endif
    </x-page-shell>

    @if($creating)
    <div class="fixed inset-0 z-[90] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <form wire:submit="create" class="w-full max-w-lg rounded-xl bg-canvas p-6 shadow-xl">
            <h2 class="mb-4 text-lg font-semibold text-ink">{{ __('Tambah Hari Libur') }}</h2>
            <div class="space-y-4">
                <div><label class="mb-1 block text-sm font-medium">{{ __('Nama Hari Libur') }} *</label><input wire:model="name" required placeholder="Libur Nasional" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Tanggal') }} *</label><input type="date" wire:model="date" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="is_active" class="h-4 w-4 rounded border-outline-variant" /> {{ __('Aktif') }}</label>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-button variant="secondary" wire:click="$set('creating', false)">{{ __('Batal') }}</x-button>
                <x-button variant="primary" type="submit">{{ __('Simpan') }}</x-button>
            </div>
        </form>
    </div>
    @endif

    @if($editing)
    <div class="fixed inset-0 z-[90] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <form wire:submit="update" class="w-full max-w-lg rounded-xl bg-canvas p-6 shadow-xl">
            <h2 class="mb-4 text-lg font-semibold text-ink">{{ __('Edit Hari Libur') }}</h2>
            <div class="space-y-4">
                <div><label class="mb-1 block text-sm font-medium">{{ __('Nama Hari Libur') }} *</label><input wire:model="name" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Tanggal') }} *</label><input type="date" wire:model="date" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="is_active" class="h-4 w-4 rounded border-outline-variant" /> {{ __('Aktif') }}</label>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-button variant="secondary" wire:click="$set('editing', false)">{{ __('Batal') }}</x-button>
                <x-button variant="primary" type="submit">{{ __('Perbarui') }}</x-button>
            </div>
        </form>
    </div>
    @endif

    @if($confirmingDeletion)
    <div class="fixed inset-0 z-[90] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <form wire:submit="delete" class="w-full max-w-sm rounded-xl bg-canvas p-6 shadow-xl text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-error/10"><span class="material-symbols-outlined text-3xl text-error">warning</span></div>
            <h3 class="text-lg font-semibold text-ink">{{ __('Hapus Hari Libur') }}</h3>
            <p class="mt-2 text-sm text-on-surface-variant">Hapus <strong>{{ $deleteName }}</strong>?</p>
            <div class="mt-6 flex justify-center gap-3">
                <x-button variant="secondary" wire:click="$set('confirmingDeletion', false)">{{ __('Batal') }}</x-button>
                <x-button variant="danger" type="submit">{{ __('Hapus') }}</x-button>
            </div>
        </form>
    </div>
    @endif
</div>