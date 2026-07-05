<div>
    <x-page-shell title="{{ __('Jabatan') }}" subtitle="{{ __('Kelola data jabatan dan gaji pokok.') }}">
        @if($this->canManage())
        <x-slot:actions>
            <x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Jabatan') }}</x-button>
        </x-slot:actions>
        @endif

        <x-slot:toolbar>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-on-surface-variant/40">
                    <span class="material-symbols-outlined text-lg">search</span>
                </span>
                <input type="search" placeholder="{{ __('Cari jabatan...') }}" wire:model.blur="search"
                    class="h-10 w-full rounded-xl border border-outline-variant bg-canvas pl-10 pr-4 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink" />
            </div>
        </x-slot:toolbar>

        @if($positions->count())
        <x-simple-table :headers="[__('Kode'), __('Jabatan'), __('Departemen'), __('Grade'), __('Gaji Pokok'), __('Aksi')]" class="hidden lg:block">
            @foreach($positions as $pos)
            <tr class="transition-colors hover:bg-surface-dim/30">
                <td class="px-4 py-3"><x-status-badge tone="neutral" :pill="true">{{ $pos->code }}</x-status-badge></td>
                <td class="px-4 py-3 font-medium text-ink">{{ $pos->name }}</td>
                <td class="px-4 py-3 text-sm text-on-surface-variant">{{ $pos->department?->name ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-on-surface-variant">{{ $pos->grade ? 'G' . $pos->grade : '-' }}</td>
                <td class="px-4 py-3 text-sm font-medium text-success">{{ $pos->basic_salary ? Number::currency($pos->basic_salary, 'IDR', app()->getLocale()) : '-' }}</td>
                <td class="px-4 py-3">
                    @if($this->canManage())
                    <div class="flex gap-1">
                        <x-button variant="ghost" size="sm" icon="edit" wire:click="edit({{ $pos->id }})">{{ __('Edit') }}</x-button>
                        <x-button variant="ghost" size="sm" icon="delete" wire:click="confirmDeletion({{ $pos->id }})" class="text-error">{{ __('Hapus') }}</x-button>
                    </div>
                    @endif
                </td>
            </tr>
            @endforeach
        </x-simple-table>

        <div class="space-y-3 lg:hidden">
            @foreach($positions as $pos)
            <div class="rounded-xl border border-outline-variant bg-canvas p-4 shadow-sm">
                <div class="flex items-start justify-between">
                    <div><h3 class="font-medium text-ink">{{ $pos->name }}</h3><p class="mt-0.5 text-sm text-on-surface-variant">{{ $pos->department?->name ?? '-' }}</p></div>
                    <x-status-badge tone="neutral" :pill="true">{{ $pos->code }}</x-status-badge>
                </div>
                <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                    <div><span class="text-on-surface-variant">{{ __('Grade') }}</span><p class="font-medium text-ink">{{ $pos->grade ? 'G' . $pos->grade : '-' }}</p></div>
                    <div><span class="text-on-surface-variant">{{ __('Gaji Pokok') }}</span><p class="font-medium text-success">{{ $pos->basic_salary ? Number::currency($pos->basic_salary, 'IDR', app()->getLocale()) : '-' }}</p></div>
                </div>
                @if($this->canManage())
                <div class="mt-3 flex justify-end gap-2 border-t border-outline-variant/50 pt-3">
                    <x-button variant="secondary" size="sm" icon="edit" wire:click="edit({{ $pos->id }})">{{ __('Edit') }}</x-button>
                    <x-button variant="secondary" size="sm" icon="delete" wire:click="confirmDeletion({{ $pos->id }})" class="text-error">{{ __('Hapus') }}</x-button>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <x-pagination :paginator="$positions" />
        @else
        <x-empty-state :title="filled($search) ? __('Tidak ada jabatan ditemukan') : __('Belum ada jabatan')" :description="filled($search) ? __('Coba ubah kata kunci pencarian.') : __('Tambahkan jabatan untuk memulai.')">
            @if($this->canManage())
            <x-slot:actions><x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Jabatan') }}</x-button></x-slot:actions>
            @endif
        </x-empty-state>
        @endif
    </x-page-shell>

    {{-- Create Modal --}}
    <x-modal wire:model="creating" max-width="lg">
        <x-slot:title>{{ __('Tambah Jabatan') }}</x-slot:title>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-forms.input label="{{ __('Kode') }}" wire:model="code" required placeholder="MGR" />
            <x-forms.input label="{{ __('Nama Jabatan') }}" wire:model="name" required placeholder="Manager" />
            <x-forms.select label="{{ __('Departemen') }}" wire:model="department_id" :options="$departments->pluck('name', 'id')" placeholder="{{ __('Pilih departemen...') }}" />
            <x-forms.input label="{{ __('Grade') }}" wire:model="grade" type="number" min="1" />
            <x-forms.input label="{{ __('Gaji Pokok (Rp)') }}" wire:model="basic_salary" type="number" min="0" />
            <x-forms.input label="{{ __('Tunjangan Jabatan (Rp)') }}" wire:model="allowance_jabatan" type="number" min="0" />
        </div>
        <x-slot:footer>
            <x-button variant="secondary" wire:click="$set('creating', false)">{{ __('Batal') }}</x-button>
            <x-button variant="primary" wire:click="create">{{ __('Simpan') }}</x-button>
        </x-slot:footer>
    </x-modal>

    {{-- Edit Modal --}}
    <x-modal wire:model="editing" max-width="lg">
        <x-slot:title>{{ __('Edit Jabatan') }}</x-slot:title>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-forms.input label="{{ __('Kode') }}" wire:model="code" required />
            <x-forms.input label="{{ __('Nama Jabatan') }}" wire:model="name" required />
            <x-forms.select label="{{ __('Departemen') }}" wire:model="department_id" :options="$departments->pluck('name', 'id')" placeholder="{{ __('Pilih departemen...') }}" />
            <x-forms.input label="{{ __('Grade') }}" wire:model="grade" type="number" min="1" />
            <x-forms.input label="{{ __('Gaji Pokok (Rp)') }}" wire:model="basic_salary" type="number" min="0" />
            <x-forms.input label="{{ __('Tunjangan Jabatan (Rp)') }}" wire:model="allowance_jabatan" type="number" min="0" />
        </div>
        <x-slot:footer>
            <x-button variant="secondary" wire:click="$set('editing', false)">{{ __('Batal') }}</x-button>
            <x-button variant="primary" wire:click="update">{{ __('Perbarui') }}</x-button>
        </x-slot:footer>
    </x-modal>

    {{-- Delete Modal --}}
    <x-confirm-modal name="delete-pos" :title="__('Hapus Jabatan')" variant="danger" wire:model="confirmingDeletion">
        <p>{{ __('Hapus jabatan') }} <strong>{{ $deleteName }}</strong>?</p>
        <x-slot:footer>
            <x-button variant="secondary" wire:click="$set('confirmingDeletion', false)">{{ __('Batal') }}</x-button>
            <x-button variant="danger" wire:click="delete">{{ __('Hapus') }}</x-button>
        </x-slot:footer>
    </x-confirm-modal>
</div>
