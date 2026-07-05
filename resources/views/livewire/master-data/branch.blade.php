<div>
    <x-page-shell title="{{ __('Cabang') }}" subtitle="{{ __('Kelola data cabang dan konfigurasi geofencing.') }}">
        @if($this->canManage())
        <x-slot:actions><x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Cabang') }}</x-button></x-slot:actions>
        @endif

        <x-slot:toolbar>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-on-surface-variant/40"><span class="material-symbols-outlined text-lg">search</span></span>
                <input type="search" wire:model="search" wire:change="$refresh" placeholder="{{ __('Cari cabang...') }}" class="h-10 w-full rounded-xl border border-outline-variant bg-canvas pl-10 pr-4 text-sm text-ink outline-none" />
            </div>
        </x-slot:toolbar>

        @if($branches->count())
        <x-simple-table :headers="[__('Nama'), __('Alamat'), __('Geofence'), __('Aksi')]" class="hidden lg:block">
            @foreach($branches as $b)
            <tr class="transition-colors hover:bg-surface-dim/30">
                <td class="px-4 py-3 font-medium text-ink">{{ $b->name }}</td>
                <td class="px-4 py-3 text-sm text-on-surface-variant">{{ \Illuminate\Support\Str::limit($b->address, 40) ?: '-' }}</td>
                <td class="px-4 py-3">
                    @if($b->latitude && $b->longitude)
                    <span class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 text-xs font-medium text-success ring-1 ring-inset ring-success/20"><span class="material-symbols-outlined text-sm">location_on</span> {{ number_format($b->latitude,4) }}, {{ number_format($b->longitude,4) }}</span>
                    <span class="ml-2 text-xs text-on-surface-variant">{{ $b->radius ? $b->radius.'m' : '-' }}</span>
                    @else
                    <span class="text-xs text-on-surface-variant/50">{{ __('Belum dikonfigurasi') }}</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($this->canManage())
                    <div class="flex gap-1">
                        <x-button variant="ghost" size="sm" icon="edit" wire:click="edit({{ $b->id }})">{{ __('Edit') }}</x-button>
                        <x-button variant="ghost" size="sm" icon="delete" wire:click="confirmDeletion({{ $b->id }})" class="text-error">{{ __('Hapus') }}</x-button>
                    </div>
                    @endif
                </td>
            </tr>
            @endforeach
        </x-simple-table>
        <x-pagination :paginator="$branches" />
        @else
        <x-empty-state :title="$search ? __('Tidak ada cabang ditemukan') : __('Belum ada cabang')" :description="$search ? __('Coba ubah kata kunci pencarian.') : __('Tambahkan cabang perusahaan untuk memulai.')">
            @if($this->canManage())<x-slot:actions><x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Cabang') }}</x-button></x-slot:actions>@endif
        </x-empty-state>
        @endif
    </x-page-shell>

    @if($creating)
    <div class="fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="$set('creating', false)" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto w-full max-w-lg overflow-hidden rounded-xl bg-canvas shadow-xl" style="max-height: calc(100dvh - 2rem)" @click.stop>
            <div class="flex items-center justify-between border-b border-outline-variant/50 px-6 py-4">
                <h2 class="text-lg font-semibold text-ink">{{ __('Tambah Cabang') }}</h2>
                <x-button variant="ghost" size="sm" icon="close" wire:click="$set('creating', false)" />
            </div>
            <div class="px-6 py-4 space-y-4">
                <x-forms.input label="{{ __('Nama Cabang') }}" wire:model="name" required />
                <x-forms.input label="{{ __('Alamat') }}" wire:model="address" />
                <x-forms.input label="{{ __('Latitude') }}" wire:model="latitude" type="number" step="any" />
                <x-forms.input label="{{ __('Longitude') }}" wire:model="longitude" type="number" step="any" />
                <x-forms.input label="{{ __('Radius (meter)') }}" wire:model="radius" type="number" min="10" max="5000" placeholder="100" />
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-outline-variant/50 bg-surface-dim/30 px-6 py-4">
                <x-button variant="secondary" wire:click="$set('creating', false)">{{ __('Batal') }}</x-button>
                <x-button variant="primary" wire:click="create">{{ __('Simpan') }}</x-button>
            </div>
        </div>
    </div>
    @endif

    @if($editing)
    <div class="fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="$set('editing', false)" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto w-full max-w-lg overflow-hidden rounded-xl bg-canvas shadow-xl" style="max-height: calc(100dvh - 2rem)" @click.stop>
            <div class="flex items-center justify-between border-b border-outline-variant/50 px-6 py-4">
                <h2 class="text-lg font-semibold text-ink">{{ __('Edit Cabang') }}</h2>
                <x-button variant="ghost" size="sm" icon="close" wire:click="$set('editing', false)" />
            </div>
            <div class="px-6 py-4 space-y-4">
                <x-forms.input label="{{ __('Nama Cabang') }}" wire:model="name" required />
                <x-forms.input label="{{ __('Alamat') }}" wire:model="address" />
                <x-forms.input label="{{ __('Latitude') }}" wire:model="latitude" type="number" step="any" />
                <x-forms.input label="{{ __('Longitude') }}" wire:model="longitude" type="number" step="any" />
                <x-forms.input label="{{ __('Radius (meter)') }}" wire:model="radius" type="number" min="10" max="5000" placeholder="100" />
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-outline-variant/50 bg-surface-dim/30 px-6 py-4">
                <x-button variant="secondary" wire:click="$set('editing', false)">{{ __('Batal') }}</x-button>
                <x-button variant="primary" wire:click="update">{{ __('Perbarui') }}</x-button>
            </div>
        </div>
    </div>
    @endif

    @if($confirmingDeletion)
    <div class="fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" wire:click="$set('confirmingDeletion', false)" aria-hidden="true"></div>
        <div class="relative z-10 mx-auto w-full max-w-sm overflow-hidden rounded-xl bg-canvas shadow-xl" @click.stop>
            <div class="px-6 py-5 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-error/10"><span class="material-symbols-outlined text-3xl text-error">warning</span></div>
                <h3 class="text-lg font-semibold text-ink">{{ __('Hapus Cabang') }}</h3>
                <p class="mt-2 text-sm text-on-surface-variant">{{ __('Yakin ingin menghapus cabang') }} <strong>{{ $deleteName }}</strong>?</p>
            </div>
            <div class="flex justify-center gap-3 border-t border-outline-variant/50 bg-surface-dim/30 px-6 py-4">
                <x-button variant="secondary" wire:click="$set('confirmingDeletion', false)">{{ __('Batal') }}</x-button>
                <x-button variant="danger" wire:click="delete">{{ __('Hapus') }}</x-button>
            </div>
        </div>
    </div>
    @endif
</div>
