<div>
    <x-page-shell title="{{ __('Cabang') }}" subtitle="{{ __('Kelola data cabang perusahaan.') }}">
        @if($this->canManage())
        <x-slot:actions>
            <x-button variant="primary" icon="add" wire:click="showCreating">
                {{ __('Tambah Cabang') }}
            </x-button>
        </x-slot:actions>
        @endif

        <x-slot:toolbar>
            <x-page-toolbar search search-placeholder="{{ __('Cari cabang...') }}" wire:model.live.debounce.300ms="search" />
        </x-slot:toolbar>

        @if($branches->count())
        {{-- Desktop --}}
        <x-simple-table :headers="[__('Nama'), __('Alamat'), __('Aksi')]" class="hidden lg:block">
            @foreach($branches as $branch)
            <tr class="transition-colors hover:bg-surface-dim/30">
                <td class="px-4 py-3">
                    <span class="font-medium text-ink">{{ $branch->name }}</span>
                </td>
                <td class="px-4 py-3">
                    <span class="text-sm text-on-surface-variant">{{ $branch->address ?? '-' }}</span>
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

        {{-- Mobile --}}
        <div class="space-y-3 lg:hidden">
            @foreach($branches as $branch)
            <div class="rounded-xl border border-outline-variant bg-canvas p-4 shadow-sm">
                <h3 class="font-medium text-ink">{{ $branch->name }}</h3>
                <p class="mt-1 text-sm text-on-surface-variant">{{ $branch->address ?? __('Tanpa alamat') }}</p>
                @if($this->canManage())
                <div class="mt-3 flex justify-end gap-2 border-t border-outline-variant/50 pt-3">
                    <x-button variant="secondary" size="sm" icon="edit" wire:click="edit({{ $branch->id }})">{{ __('Edit') }}</x-button>
                    <x-button variant="secondary" size="sm" icon="delete" wire:click="confirmDeletion({{ $branch->id }})" class="text-error">{{ __('Hapus') }}</x-button>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <x-pagination :paginator="$branches" />
        @else
        <x-empty-state :title="filled($search) ? __('Tidak ada cabang ditemukan') : __('Belum ada cabang')" :description="filled($search) ? __('Coba ubah kata kunci pencarian.') : __('Tambahkan cabang perusahaan untuk memulai.')">
            @if($this->canManage())
            <x-slot:actions>
                <x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Cabang') }}</x-button>
            </x-slot:actions>
            @endif
        </x-empty-state>
        @endif
    </x-page-shell>

    {{-- Create Modal --}}
    <x-form-modal name="create-branch" :title="__('Tambah Cabang')" wire:model="creating">
        <div class="space-y-4">
            <x-forms.input label="{{ __('Nama Cabang') }}" wire:model="name" required />
            <x-forms.textarea label="{{ __('Alamat') }}" wire:model="address" rows="2" />
        </div>
        <x-slot:footer>
            <x-button variant="secondary" wire:click="$set('creating', false)">{{ __('Batal') }}</x-button>
            <x-button variant="primary" wire:click="create">{{ __('Simpan') }}</x-button>
        </x-slot:footer>
    </x-form-modal>

    {{-- Edit Modal --}}
    <x-form-modal name="edit-branch" :title="__('Edit Cabang')" wire:model="editing">
        <div class="space-y-4">
            <x-forms.input label="{{ __('Nama Cabang') }}" wire:model="name" required />
            <x-forms.textarea label="{{ __('Alamat') }}" wire:model="address" rows="2" />
        </div>
        <x-slot:footer>
            <x-button variant="secondary" wire:click="$set('editing', false)">{{ __('Batal') }}</x-button>
            <x-button variant="primary" wire:click="update">{{ __('Perbarui') }}</x-button>
        </x-slot:footer>
    </x-form-modal>

    {{-- Delete Confirmation --}}
    <x-confirm-modal name="delete-branch" :title="__('Hapus Cabang')" variant="danger" wire:model="confirmingDeletion">
        <p>{{ __('Anda yakin ingin menghapus cabang') }} <strong>{{ $deleteName }}</strong>?</p>
        @if($deleteAddress)
        <p class="mt-1 text-sm text-on-surface-variant">{{ $deleteAddress }}</p>
        @endif
        <x-slot:footer>
            <x-button variant="secondary" wire:click="$set('confirmingDeletion', false)">{{ __('Batal') }}</x-button>
            <x-button variant="danger" wire:click="delete">{{ __('Hapus') }}</x-button>
        </x-slot:footer>
    </x-confirm-modal>
</div>
