<div>
    <x-page-shell title="{{ __('Cabang') }}" subtitle="{{ __('Kelola data cabang dan konfigurasi geofencing.') }}">
        <div class="flex flex-wrap items-center gap-3">
            @if($this->canManage())
            <x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Cabang') }}</x-button>
            @endif
            <div class="min-w-[200px] flex-1">
                <input type="search" wire:model.live.debounce.250ms="search" placeholder="{{ __('Cari cabang...') }}" class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-4 text-sm text-ink outline-none" />
            </div>
        </div>

        @if($branches->count())
        <x-simple-table :headers="[__('Nama'), __('Status'), __('Alamat'), __('Geofence'), __('Aksi')]">
            @foreach($branches as $b)
            <tr>
                <td class="px-4 py-3 font-medium text-ink">{{ $b->name }}</td>
                <td class="px-4 py-3">
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $b->is_active ? 'bg-success/10 text-success' : 'bg-error/10 text-error' }}">
                        {{ $b->is_active ? __('Aktif') : __('Nonaktif') }}
                    </span>
                </td>
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
        <form wire:submit="create" class="w-full max-w-lg rounded-xl bg-canvas p-6 shadow-xl">
            <h2 class="mb-4 text-lg font-semibold text-ink">{{ __('Tambah Cabang') }}</h2>
            <div class="space-y-4">
                <div><label class="mb-1 block text-sm font-medium">{{ __('Nama Cabang') }} *</label><input wire:model="name" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Alamat') }}</label><input wire:model="address" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="mb-1 block text-sm font-medium">{{ __('Latitude') }}</label><input wire:model="latitude" type="number" step="any" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                    <div><label class="mb-1 block text-sm font-medium">{{ __('Longitude') }}</label><input wire:model="longitude" type="number" step="any" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                </div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Radius (meter)') }}</label><input wire:model="radius" type="number" min="10" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div class="flex items-center gap-3">
                    <input type="hidden" wire:model="isActive" value="0" />
                    <input type="checkbox" wire:model="isActive" value="1" id="create-active" class="rounded border-outline-variant" />
                    <label for="create-active" class="text-sm text-on-surface-variant">{{ __('Cabang Aktif') }}</label>
                </div>
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
            <h2 class="mb-4 text-lg font-semibold text-ink">{{ __('Edit Cabang') }}</h2>
            <div class="space-y-4">
                <div><label class="mb-1 block text-sm font-medium">{{ __('Nama Cabang') }} *</label><input wire:model="name" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Alamat') }}</label><input wire:model="address" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="mb-1 block text-sm font-medium">{{ __('Latitude') }}</label><input wire:model="latitude" type="number" step="any" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                    <div><label class="mb-1 block text-sm font-medium">{{ __('Longitude') }}</label><input wire:model="longitude" type="number" step="any" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                </div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Radius (meter)') }}</label><input wire:model="radius" type="number" min="10" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div class="flex items-center gap-3">
                    <input type="hidden" wire:model="isActive" value="0" />
                    <input type="checkbox" wire:model="isActive" value="1" id="edit-active" class="rounded border-outline-variant" />
                    <label for="edit-active" class="text-sm text-on-surface-variant">{{ __('Cabang Aktif') }}</label>
                </div>
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
            <h3 class="text-lg font-semibold text-ink">{{ __('Hapus Cabang') }}</h3>
            <p class="mt-2 text-sm text-on-surface-variant">Hapus <strong>{{ $deleteName }}</strong>?</p>
            <div class="mt-6 flex justify-center gap-3">
                <x-button variant="secondary" wire:click="$set('confirmingDeletion', false)">{{ __('Batal') }}</x-button>
                <x-button variant="danger" type="submit">{{ __('Hapus') }}</x-button>
            </div>
        </form>
    </div>
    @endif
</div>
