<div>
    <x-page-shell title="{{ __('Cabang') }}" subtitle="{{ __('Kelola data cabang dan konfigurasi geofencing.') }}">
        <div class="flex flex-wrap items-center gap-3">
            @if($this->canManage())
            <x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Cabang') }}</x-button>
            @endif
        </div>

        @if($branches->count())
        <x-simple-table :headers="[__('Nama'), __('Alamat'), __('Geofence'), __('Aksi')]">
            @foreach($branches as $b)
            <tr>
                <td class="px-4 py-3 font-medium text-ink">{{ $b->name }}</td>
                <td class="px-4 py-3 text-sm text-on-surface-variant">{{ \Illuminate\Support\Str::limit($b->address, 40) ?: '-' }}</td>
                <td class="px-4 py-3">
                    @if($b->latitude && $b->longitude)
                    <span class="rounded-full bg-success/10 px-2 py-0.5 text-xs text-success">{{ number_format($b->latitude,4) }}, {{ number_format($b->longitude,4) }} @if($b->radius) · {{ $b->radius }}m @endif</span>
                    @else
                    <span class="text-xs text-on-surface-variant/50">-</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($this->canManage())
                    <div class="flex gap-1">
                        <x-button variant="ghost" size="sm" wire:click="edit({{ $b->id }})">{{ __('Edit') }}</x-button>
                        <x-button variant="ghost" size="sm" wire:click="confirmDeletion({{ $b->id }})">{{ __('Hapus') }}</x-button>
                    </div>
                    @endif
                </td>
            </tr>
            @endforeach
        </x-simple-table>
        <x-pagination :paginator="$branches" />
        @else
        <x-empty-state title="{{ filled($search) ? __('Tidak ada cabang ditemukan') : __('Belum ada cabang') }}" description="{{ filled($search) ? __('Coba ubah kata kunci.') : __('Tambahkan cabang perusahaan.') }}" />
        @endif
    </x-page-shell>

    @if($creating)
    <div class="fixed inset-0 z-[90] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <div class="w-full max-w-lg rounded-xl bg-canvas p-6 shadow-xl">
            <h2 class="mb-4 text-lg font-semibold text-ink">{{ __('Tambah Cabang') }}</h2>
            <div class="space-y-4">
                <div><label class="mb-1 block text-sm font-medium">{{ __('Nama Cabang') }} *</label><input wire:model="name" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Alamat') }}</label><input wire:model="address" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="mb-1 block text-sm font-medium">{{ __('Latitude') }}</label><input wire:model="latitude" type="number" step="any" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                    <div><label class="mb-1 block text-sm font-medium">{{ __('Longitude') }}</label><input wire:model="longitude" type="number" step="any" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                </div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Radius (meter)') }}</label><input wire:model="radius" type="number" min="10" placeholder="100" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-button variant="secondary" wire:click="$set('creating', false)">{{ __('Batal') }}</x-button>
                <x-button variant="primary" wire:click="create">{{ __('Simpan') }}</x-button>
            </div>
        </div>
    </div>
    @endif

    @if($editing)
    <div class="fixed inset-0 z-[90] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <div class="w-full max-w-lg rounded-xl bg-canvas p-6 shadow-xl">
            <h2 class="mb-4 text-lg font-semibold text-ink">{{ __('Edit Cabang') }}</h2>
            <div class="space-y-4">
                <div><label class="mb-1 block text-sm font-medium">{{ __('Nama Cabang') }} *</label><input wire:model="name" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Alamat') }}</label><input wire:model="address" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="mb-1 block text-sm font-medium">{{ __('Latitude') }}</label><input wire:model="latitude" type="number" step="any" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                    <div><label class="mb-1 block text-sm font-medium">{{ __('Longitude') }}</label><input wire:model="longitude" type="number" step="any" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                </div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Radius (meter)') }}</label><input wire:model="radius" type="number" min="10" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-button variant="secondary" wire:click="$set('editing', false)">{{ __('Batal') }}</x-button>
                <x-button variant="primary" wire:click="update">{{ __('Perbarui') }}</x-button>
            </div>
        </div>
    </div>
    @endif

    @if($confirmingDeletion)
    <div class="fixed inset-0 z-[90] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <div class="w-full max-w-sm rounded-xl bg-canvas p-6 shadow-xl text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-error/10"><span class="material-symbols-outlined text-3xl text-error">warning</span></div>
            <h3 class="text-lg font-semibold text-ink">{{ __('Hapus Cabang') }}</h3>
            <p class="mt-2 text-sm text-on-surface-variant">Hapus <strong>{{ $deleteName }}</strong>?</p>
            <div class="mt-6 flex justify-center gap-3">
                <x-button variant="secondary" wire:click="$set('confirmingDeletion', false)">{{ __('Batal') }}</x-button>
                <x-button variant="danger" wire:click="delete">{{ __('Hapus') }}</x-button>
            </div>
        </div>
    </div>
    @endif
</div>
