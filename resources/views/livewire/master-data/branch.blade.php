<div>
    <x-page-shell title="{{ __('Cabang') }}" subtitle="{{ __('Kelola data cabang dan konfigurasi geofencing.') }}">
        <x-slot:actions>
            <x-button variant="primary" icon="add" wire:click="showCreating">
                {{ __('Tambah Cabang') }}
            </x-button>
        </x-slot:actions>

        <x-slot:toolbar>
            <div class="flex items-end gap-4">
                <div class="flex-1">
                    <x-forms.label for="branch-search" value="{{ __('Cari cabang') }}" class="mb-1.5 block" />
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-on-surface-variant/40">
                            <span class="material-symbols-outlined text-lg">search</span>
                        </span>
                        <input id="branch-search" type="search" wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Cari nama cabang...') }}" class="w-full pl-10 h-10 rounded-xl border border-outline-variant bg-canvas text-sm text-ink outline-none" />
                    </div>
                </div>
            </div>
        </x-slot:toolbar>

        @if($branches->count())
        <x-simple-table :headers="[__('Nama'), __('Alamat'), __('Geofence'), __('Aksi')]" class="hidden lg:block">
            @foreach($branches as $branch)
            <tr class="group transition-colors hover:bg-surface-dim/30">
                <td class="px-4 py-3">
                    <div class="font-medium text-ink">{{ $branch->name }}</div>
                    @if($branch->address)<div class="text-xs text-on-surface-variant">{{ \Illuminate\Support\Str::limit($branch->address, 50) }}</div>@endif
                </td>
                <td class="px-4 py-3">
                    <span class="text-sm text-on-surface-variant">{{ \Illuminate\Support\Str::limit($branch->address, 40) ?: '-' }}</span>
                </td>
                <td class="px-4 py-3">
                    @if($branch->latitude && $branch->longitude)
                    <span class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 text-xs font-medium text-success ring-1 ring-inset ring-success/20">
                        <span class="material-symbols-outlined text-sm">location_on</span>
                        {{ number_format($branch->latitude, 4) }}, {{ number_format($branch->longitude, 4) }}
                    </span>
                    <span class="ml-2 text-xs text-on-surface-variant">{{ $branch->radius ? $branch->radius.'m' : '-' }}</span>
                    @else
                    <span class="text-xs text-on-surface-variant/50">{{ __('Belum dikonfigurasi') }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex justify-end gap-2">
                        <x-button variant="ghost" size="sm" icon="edit" wire:click="edit({{ $branch->id }})">{{ __('Edit') }}</x-button>
                        <x-button variant="ghost" size="sm" icon="delete" wire:click="confirmDeletion({{ $branch->id }})" class="text-error">{{ __('Hapus') }}</x-button>
                    </div>
                </td>
            </tr>
            @endforeach
        </x-simple-table>

        @if($branches->hasPages())
        <div class="border-t border-outline-variant/50 bg-surface-dim/30 px-4 py-2.5">
            {{ $branches->onEachSide(1)->links() }}
        </div>
        @endif
        @else
        <x-empty-state :title="filled($search) ? __('Tidak ada cabang ditemukan') : __('Belum ada cabang')" :description="filled($search) ? __('Coba ubah kata kunci pencarian.') : __('Tambahkan cabang perusahaan untuk memulai.')">
            <x-slot:actions>
                <x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Cabang') }}</x-button>
            </x-slot:actions>
        </x-empty-state>
        @endif
    </x-page-shell>

    <x-confirm-modal wire:model="confirmingDeletion" :title="__('Hapus Cabang')" variant="danger">
        <p>{{ __('Yakin ingin menghapus cabang') }} <strong>{{ $deleteName }}</strong>?</p>
        <x-slot:footer>
            <x-button variant="secondary" @click="$wire.set('confirmingDeletion', false)" wire:loading.attr="disabled">{{ __('Batal') }}</x-button>
            <x-button variant="danger" @click="$wire.call('delete')" wire:loading.attr="disabled">{{ __('Hapus') }}</x-button>
        </x-slot:footer>
    </x-confirm-modal>

    <x-modal wire:model="creating" max-width="lg" id="create-branch-modal">
        <div class="px-6 py-4">
            <div class="text-lg font-medium text-ink">{{ __('Tambah Cabang') }}</div>
            <div class="mt-4 space-y-4">
                <x-forms.input label="{{ __('Nama Cabang') }}" wire:model="name" required />
                <x-forms.input label="{{ __('Alamat') }}" wire:model="address" />
                <x-forms.input label="{{ __('Latitude') }}" wire:model="latitude" type="number" step="any" />
                <x-forms.input label="{{ __('Longitude') }}" wire:model="longitude" type="number" step="any" />
                <x-forms.input label="{{ __('Radius (meter)') }}" wire:model="radius" type="number" min="10" max="5000" placeholder="100" />
            </div>
        </div>
        <div class="flex flex-row justify-end border-t border-outline-variant/50 bg-surface-dim/30 px-6 py-4">
            <x-button variant="secondary" @click="$wire.set('creating', false)" wire:loading.attr="disabled">{{ __('Batal') }}</x-button>
            <x-button variant="primary" class="ml-2" @click="$wire.call('create')" wire:loading.attr="disabled">{{ __('Simpan') }}</x-button>
        </div>
    </x-modal>

    <x-modal wire:model="editing" max-width="lg" id="edit-branch-modal">
        <div class="px-6 py-4">
            <div class="text-lg font-medium text-ink">{{ __('Edit Cabang') }}</div>
            <div class="mt-4 space-y-4">
                <x-forms.input label="{{ __('Nama Cabang') }}" wire:model="name" required />
                <x-forms.input label="{{ __('Alamat') }}" wire:model="address" />
                <x-forms.input label="{{ __('Latitude') }}" wire:model="latitude" type="number" step="any" />
                <x-forms.input label="{{ __('Longitude') }}" wire:model="longitude" type="number" step="any" />
                <x-forms.input label="{{ __('Radius (meter)') }}" wire:model="radius" type="number" min="10" max="5000" placeholder="100" />
            </div>
        </div>
        <div class="flex flex-row justify-end border-t border-outline-variant/50 bg-surface-dim/30 px-6 py-4">
            <x-button variant="secondary" @click="$wire.set('editing', false)" wire:loading.attr="disabled">{{ __('Batal') }}</x-button>
            <x-button variant="primary" class="ml-2" @click="$wire.call('update')" wire:loading.attr="disabled">{{ __('Perbarui') }}</x-button>
        </div>
    </x-modal>
</div>
