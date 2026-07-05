<div>
    <x-page-shell title="{{ __('Departemen') }}" subtitle="{{ __('Kelola data departemen.') }}">
        <div class="flex flex-wrap items-center gap-3">
            @if($this->canManage())
            <x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Departemen') }}</x-button>
            @endif
            <div class="min-w-[200px] flex-1">
                <input type="search" wire:model.live.debounce.250ms="search" placeholder="{{ __('Cari departemen...') }}" class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-4 text-sm text-ink outline-none" />
            </div>
        </div>

        @if($departments->count())
        <x-simple-table :headers="[__('Kode'), __('Nama'), __('Cabang'), __('Aksi')]">
            @foreach($departments as $dept)
            <tr>
                <td class="px-4 py-3"><x-status-badge tone="neutral" :pill="true">{{ $dept->code }}</x-status-badge></td>
                <td class="px-4 py-3 font-medium text-ink">{{ $dept->name }}</td>
                <td class="px-4 py-3 text-sm text-on-surface-variant">{{ $dept->branch?->name ?? '-' }}</td>
                <td class="px-4 py-3">
                    @if($this->canManage())
                    <div class="flex gap-1">
                        <x-button variant="ghost" size="sm" wire:click="edit({{ $dept->id }})">{{ __('Edit') }}</x-button>
                        <x-button variant="ghost" size="sm" wire:click="confirmDeletion({{ $dept->id }})">{{ __('Hapus') }}</x-button>
                    </div>
                    @endif
                </td>
            </tr>
            @endforeach
        </x-simple-table>
        <x-pagination :paginator="$departments" />
        @else
        <x-empty-state :title="filled($search) ? __('Tidak ada departemen ditemukan') : __('Belum ada departemen')" :description="filled($search) ? __('Coba ubah kata kunci.') : __('Tambahkan departemen untuk memulai.')" />
        @endif
    </x-page-shell>

    @if($creating)
    <div class="fixed inset-0 z-[90] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <form wire:submit="create" class="w-full max-w-lg rounded-xl bg-canvas p-6 shadow-xl">
            <h2 class="mb-4 text-lg font-semibold text-ink">{{ __('Tambah Departemen') }}</h2>
            <div class="space-y-4">
                <div><label class="mb-1 block text-sm font-medium">{{ __('Kode') }} *</label><input wire:model="code" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Nama Departemen') }} *</label><input wire:model="name" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('Cabang') }}</label>
                    <select wire:model="branch_id" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm">
                        <option value="">{{ __('Pilih cabang...') }}</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
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
            <h2 class="mb-4 text-lg font-semibold text-ink">{{ __('Edit Departemen') }}</h2>
            <div class="space-y-4">
                <div><label class="mb-1 block text-sm font-medium">{{ __('Kode') }} *</label><input wire:model="code" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Nama Departemen') }} *</label><input wire:model="name" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div>
                    <label class="mb-1 block text-sm font-medium">{{ __('Cabang') }}</label>
                    <select wire:model="branch_id" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm">
                        <option value="">{{ __('Pilih cabang...') }}</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
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
            <h3 class="text-lg font-semibold text-ink">{{ __('Hapus Departemen') }}</h3>
            <p class="mt-2 text-sm text-on-surface-variant">Hapus <strong>{{ $deleteName }}</strong>?</p>
            <div class="mt-6 flex justify-center gap-3">
                <x-button variant="secondary" wire:click="$set('confirmingDeletion', false)">{{ __('Batal') }}</x-button>
                <x-button variant="danger" type="submit">{{ __('Hapus') }}</x-button>
            </div>
        </form>
    </div>
    @endif
</div>
