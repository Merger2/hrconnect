<div>
    <x-page-shell title="{{ __('Tipe Cuti') }}" subtitle="{{ __('Kelola jenis cuti, kuota, dan kebijakan potongan.') }}">
        <div class="flex flex-wrap items-center gap-3">
            @if($this->canManage())
            <x-button variant="primary" icon="add" wire:click="showCreating">{{ __('Tambah Tipe Cuti') }}</x-button>
            @endif
            <div class="min-w-[200px] flex-1">
                <input type="search" wire:model.live.debounce.250ms="search" placeholder="{{ __('Cari tipe cuti...') }}" class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-4 text-sm text-ink outline-none" />
            </div>
        </div>

        @if($leaveTypes->count())
        <x-simple-table :headers="[__('Kode'), __('Nama'), __('Kuota'), __('Dibayar'), __('Potong Kuota'), __('Status'), __('Aksi')]">
            @foreach($leaveTypes as $lt)
            <tr>
                <td class="px-4 py-3"><x-status-badge tone="neutral" :pill="true">{{ $lt->code }}</x-status-badge></td>
                <td class="px-4 py-3 font-medium text-ink">{{ $lt->name }}</td>
                <td class="px-4 py-3 text-sm tabular-nums">{{ $lt->quota }} hari</td>
                <td class="px-4 py-3">
                    @if($lt->is_paid)
                    <x-status-badge tone="success" :pill="true">{{ __('Ya') }}</x-status-badge>
                    @else
                    <x-status-badge tone="warning" :pill="true">{{ __('Tidak') }}</x-status-badge>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($lt->deducts_from_quota)
                    <span class="material-symbols-outlined text-lg text-success">check_circle</span>
                    @else
                    <span class="material-symbols-outlined text-lg text-on-surface-variant/40">cancel</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($lt->is_active)
                    <x-status-badge tone="success" :pill="true">{{ __('Aktif') }}</x-status-badge>
                    @else
                    <x-status-badge tone="neutral" :pill="true">{{ __('Nonaktif') }}</x-status-badge>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($this->canManage())
                    <div class="flex gap-1">
                        <x-button variant="ghost" size="sm" wire:click="edit({{ $lt->id }})">{{ __('Edit') }}</x-button>
                        <x-button variant="ghost" size="sm" wire:click="toggleActive({{ $lt->id }})">{{ $lt->is_active ? __('Nonaktifkan') : __('Aktifkan') }}</x-button>
                        <x-button variant="ghost" size="sm" wire:click="confirmDeletion({{ $lt->id }})">{{ __('Hapus') }}</x-button>
                    </div>
                    @endif
                </td>
            </tr>
            @endforeach
        </x-simple-table>
        <x-pagination :paginator="$leaveTypes" />
        @else
        <x-empty-state :title="filled($search) ? __('Tidak ada tipe cuti ditemukan') : __('Belum ada tipe cuti')" :description="filled($search) ? __('Coba ubah kata kunci.') : __('Tambahkan tipe cuti untuk memulai.')" />
        @endif
    </x-page-shell>

    @if($creating)
    <div class="fixed inset-0 z-[90] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <form wire:submit="create" class="w-full max-w-lg rounded-xl bg-canvas p-6 shadow-xl">
            <h2 class="mb-4 text-lg font-semibold text-ink">{{ __('Tambah Tipe Cuti') }}</h2>
            <div class="space-y-4">
                <div><label class="mb-1 block text-sm font-medium">{{ __('Nama Tipe Cuti') }} *</label><input wire:model="name" required placeholder="Cuti Tahunan" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Kode') }} *</label><input wire:model="code" required placeholder="ANNUAL" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm uppercase" /></div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Kuota (hari)') }} *</label><input type="number" wire:model="quota" min="0" max="365" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="is_paid" class="h-4 w-4 rounded border-outline-variant" /> {{ __('Cuti dibayar') }}</label>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="deducts_from_quota" class="h-4 w-4 rounded border-outline-variant" /> {{ __('Potong dari kuota tahunan') }}</label>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="eligible_for_carry_forward" class="h-4 w-4 rounded border-outline-variant" /> {{ __('Bisa dibawa ke tahun depan') }}</label>
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
            <h2 class="mb-4 text-lg font-semibold text-ink">{{ __('Edit Tipe Cuti') }}</h2>
            <div class="space-y-4">
                <div><label class="mb-1 block text-sm font-medium">{{ __('Nama Tipe Cuti') }} *</label><input wire:model="name" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Kode') }} *</label><input wire:model="code" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm uppercase" /></div>
                <div><label class="mb-1 block text-sm font-medium">{{ __('Kuota (hari)') }} *</label><input type="number" wire:model="quota" min="0" max="365" required class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm" /></div>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="is_paid" class="h-4 w-4 rounded border-outline-variant" /> {{ __('Cuti dibayar') }}</label>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="deducts_from_quota" class="h-4 w-4 rounded border-outline-variant" /> {{ __('Potong dari kuota tahunan') }}</label>
                <label class="flex items-center gap-2"><input type="checkbox" wire:model="eligible_for_carry_forward" class="h-4 w-4 rounded border-outline-variant" /> {{ __('Bisa dibawa ke tahun depan') }}</label>
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
            <h3 class="text-lg font-semibold text-ink">{{ __('Hapus Tipe Cuti') }}</h3>
            <p class="mt-2 text-sm text-on-surface-variant">Hapus <strong>{{ $deleteName }}</strong>?</p>
            <div class="mt-6 flex justify-center gap-3">
                <x-button variant="secondary" wire:click="$set('confirmingDeletion', false)">{{ __('Batal') }}</x-button>
                <x-button variant="danger" type="submit">{{ __('Hapus') }}</x-button>
            </div>
        </form>
    </div>
    @endif
</div>