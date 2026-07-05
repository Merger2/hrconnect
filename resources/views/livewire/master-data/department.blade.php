<div>
    <x-page-shell title="{{ __('Departemen') }}" subtitle="{{ __('Kelola data departemen.') }}">
        @if($this->canManage())
        <x-slot:actions>
            <x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Departemen') }}</x-button>
        </x-slot:actions>
        @endif

        <x-slot:toolbar>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-on-surface-variant/40">
                    <span class="material-symbols-outlined text-lg">search</span>
                </span>
                <input type="search" placeholder="{{ __('Cari departemen...') }}" wire:model.live.debounce.300ms="search"
                    class="h-10 w-full rounded-xl border border-outline-variant bg-canvas pl-10 pr-4 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink" />
            </div>
        </x-slot:toolbar>

        @if($departments->count())
        <x-simple-table :headers="[__('Kode'), __('Nama'), __('Cabang'), __('Aksi')]" class="hidden lg:block">
            @foreach($departments as $dept)
            <tr class="transition-colors hover:bg-surface-dim/30">
                <td class="px-4 py-3"><x-status-badge tone="neutral" :pill="true">{{ $dept->code }}</x-status-badge></td>
                <td class="px-4 py-3"><span class="font-medium text-ink">{{ $dept->name }}</span></td>
                <td class="px-4 py-3"><span class="text-sm text-on-surface-variant">{{ $dept->branch?->name ?? '-' }}</span></td>
                <td class="px-4 py-3">
                    @if($this->canManage())
                    <div class="flex gap-1">
                        <x-button variant="ghost" size="sm" icon="edit" wire:click="edit({{ $dept->id }})">{{ __('Edit') }}</x-button>
                        <x-button variant="ghost" size="sm" icon="delete" wire:click="confirmDeletion({{ $dept->id }})" class="text-error">{{ __('Hapus') }}</x-button>
                    </div>
                    @endif
                </td>
            </tr>
            @endforeach
        </x-simple-table>

        <div class="space-y-3 lg:hidden">
            @foreach($departments as $dept)
            <div class="rounded-xl border border-outline-variant bg-canvas p-4 shadow-sm">
                <div class="flex items-start justify-between">
                    <div><h3 class="font-medium text-ink">{{ $dept->name }}</h3><p class="mt-0.5 text-sm text-on-surface-variant">{{ $dept->branch?->name ?? '-' }}</p></div>
                    <x-status-badge tone="neutral" :pill="true">{{ $dept->code }}</x-status-badge>
                </div>
                @if($this->canManage())
                <div class="mt-3 flex justify-end gap-2 border-t border-outline-variant/50 pt-3">
                    <x-button variant="secondary" size="sm" icon="edit" wire:click="edit({{ $dept->id }})">{{ __('Edit') }}</x-button>
                    <x-button variant="secondary" size="sm" icon="delete" wire:click="confirmDeletion({{ $dept->id }})" class="text-error">{{ __('Hapus') }}</x-button>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <x-pagination :paginator="$departments" />
        @else
        <x-empty-state :title="filled($search) ? __('Tidak ada departemen ditemukan') : __('Belum ada departemen')" :description="filled($search) ? __('Coba ubah kata kunci pencarian.') : __('Tambahkan departemen untuk memulai.')">
            @if($this->canManage())
            <x-slot:actions><x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Departemen') }}</x-button></x-slot:actions>
            @endif
        </x-empty-state>
        @endif
    </x-page-shell>

    {{-- Create Modal --}}
    <x-modal wire:model="creating" max-width="lg">
        <x-slot:title>{{ __('Tambah Departemen') }}</x-slot:title>
        <div class="space-y-4">
            <x-forms.input label="{{ __('Kode') }}" wire:model="code" required placeholder="HR" />
            <x-forms.input label="{{ __('Nama Departemen') }}" wire:model="name" required placeholder="Human Resources" />
            <x-forms.select label="{{ __('Cabang') }}" wire:model="branch_id" :options="$branches->pluck('name', 'id')" placeholder="{{ __('Pilih cabang...') }}" />
        </div>
        <x-slot:footer>
            <x-button variant="secondary" wire:click="$set('creating', false)">{{ __('Batal') }}</x-button>
            <x-button variant="primary" wire:click="create">{{ __('Simpan') }}</x-button>
        </x-slot:footer>
    </x-modal>

    {{-- Edit Modal --}}
    <x-modal wire:model="editing" max-width="lg">
        <x-slot:title>{{ __('Edit Departemen') }}</x-slot:title>
        <div class="space-y-4">
            <x-forms.input label="{{ __('Kode') }}" wire:model="code" required />
            <x-forms.input label="{{ __('Nama Departemen') }}" wire:model="name" required />
            <x-forms.select label="{{ __('Cabang') }}" wire:model="branch_id" :options="$branches->pluck('name', 'id')" placeholder="{{ __('Pilih cabang...') }}" />
        </div>
        <x-slot:footer>
            <x-button variant="secondary" wire:click="$set('editing', false)">{{ __('Batal') }}</x-button>
            <x-button variant="primary" wire:click="update">{{ __('Perbarui') }}</x-button>
        </x-slot:footer>
    </x-modal>

    {{-- Delete Modal --}}
    <x-confirm-modal name="delete-dept" :title="__('Hapus Departemen')" variant="danger" wire:model="confirmingDeletion">
        <p>{{ __('Hapus departemen') }} <strong>{{ $deleteName }}</strong>?</p>
        <x-slot:footer>
            <x-button variant="secondary" wire:click="$set('confirmingDeletion', false)">{{ __('Batal') }}</x-button>
            <x-button variant="danger" wire:click="delete">{{ __('Hapus') }}</x-button>
        </x-slot:footer>
    </x-confirm-modal>
</div>
