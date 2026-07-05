<div>
    <x-page-shell title="{{ __('Cabang') }}" subtitle="{{ __('Kelola data cabang dan konfigurasi geofencing.') }}">
        @if($this->canManage())
        <x-slot:actions><x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Cabang') }}</x-button></x-slot:actions>
        @endif
        <x-slot:toolbar>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-on-surface-variant/40"><span class="material-symbols-outlined text-lg">search</span></span>
                <input type="search" placeholder="{{ __('Cari cabang...') }}" wire:model.blur="search" class="h-10 w-full rounded-xl border border-outline-variant bg-canvas pl-10 pr-4 text-sm text-ink outline-none" />
            </div>
        </x-slot:toolbar>

        @if($branches->count())
        <x-simple-table :headers="[__('Nama'), __('Alamat'), __('Geofence'), __('Aksi')]" class="hidden lg:block">
            @foreach($branches as $branch)
            <tr class="transition-colors hover:bg-surface-dim/30">
                <td class="px-4 py-3"><span class="font-medium text-ink">{{ $branch->name }}</span></td>
                <td class="px-4 py-3"><span class="text-sm text-on-surface-variant">{{ \Illuminate\Support\Str::limit($branch->address, 40) ?: '-' }}</span></td>
                <td class="px-4 py-3">
                    @if($branch->latitude && $branch->longitude)
                    <span class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 text-xs font-medium text-success ring-1 ring-inset ring-success/20"><span class="material-symbols-outlined text-sm">location_on</span> {{ number_format($branch->latitude, 4) }}, {{ number_format($branch->longitude, 4) }}</span>
                    <span class="ml-2 text-xs text-on-surface-variant">{{ $branch->radius ? $branch->radius . 'm' : '-' }}</span>
                    @else
                    <span class="text-xs text-on-surface-variant/50">{{ __('Belum dikonfigurasi') }}</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($this->canManage())
                    <div class="flex gap-1">
                        <x-button variant="ghost" size="sm" icon="edit" wire:click="edit({{ $branch->id }})">{{ __('Edit') }}</x-button>
                        <x-button variant="ghost" size="sm" icon="delete" wire:click="confirmDeletion({{ $branch->id }})" class="text-error">{{ __('Hapus') }}</x-button>
                    </div>
                    @endif
                </td>
            </tr>
            @endforeach
        </x-simple-table>
        <x-pagination :paginator="$branches" />
        @else
        <x-empty-state :title="filled($search) ? __('Tidak ada cabang ditemukan') : __('Belum ada cabang')" :description="filled($search) ? __('Coba ubah kata kunci pencarian.') : __('Tambahkan cabang perusahaan untuk memulai.')">
            @if($this->canManage())<x-slot:actions><x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Cabang') }}</x-button></x-slot:actions>@endif
        </x-empty-state>
        @endif
    </x-page-shell>

    <x-modal wire:model="creating" max-width="lg">
        <x-slot:title>{{ __('Tambah Cabang') }}</x-slot:title>
        <div class="space-y-4">
            <x-forms.input label="{{ __('Nama Cabang') }}" wire:model="name" required />
            <x-forms.input label="{{ __('Alamat') }}" wire:model="address" />
            <x-forms.input label="{{ __('Latitude') }}" wire:model="latitude" type="number" step="any" />
            <x-forms.input label="{{ __('Longitude') }}" wire:model="longitude" type="number" step="any" />
            <x-forms.input label="{{ __('Radius (meter)') }}" wire:model="radius" type="number" min="10" max="5000" placeholder="100" />
        </div>
        <x-slot:footer>
            <x-button variant="secondary" wire:click="$set('creating', false)">{{ __('Batal') }}</x-button>
            <x-button variant="primary" wire:click="create">{{ __('Simpan') }}</x-button>
        </x-slot:footer>
    </x-modal>

    <x-modal wire:model="editing" max-width="lg">
        <x-slot:title>{{ __('Edit Cabang') }}</x-slot:title>
        <div class="space-y-4">
            <x-forms.input label="{{ __('Nama Cabang') }}" wire:model="name" required />
            <x-forms.input label="{{ __('Alamat') }}" wire:model="address" />
            <x-forms.input label="{{ __('Latitude') }}" wire:model="latitude" type="number" step="any" />
            <x-forms.input label="{{ __('Longitude') }}" wire:model="longitude" type="number" step="any" />
            <x-forms.input label="{{ __('Radius (meter)') }}" wire:model="radius" type="number" min="10" max="5000" placeholder="100" />
        </div>
        <x-slot:footer>
            <x-button variant="secondary" wire:click="$set('editing', false)">{{ __('Batal') }}</x-button>
            <x-button variant="primary" wire:click="update">{{ __('Perbarui') }}</x-button>
        </x-slot:footer>
    </x-modal>

    <x-confirm-modal name="delete-branch" :title="__('Hapus Cabang')" variant="danger">
        <p>{{ __('Anda yakin ingin menghapus cabang') }} <strong>{{ $deleteName }}</strong>?</p>
        <x-slot:actions>
            <x-button variant="secondary" wire:click="$set('confirmingDeletion', false)">{{ __('Batal') }}</x-button>
            <x-button variant="danger" wire:click="delete">{{ __('Hapus') }}</x-button>
        </x-slot:actions>
    </x-confirm-modal>
</div>
