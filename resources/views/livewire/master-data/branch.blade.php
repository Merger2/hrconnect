<div>
    <x-page-shell title="{{ __('Cabang') }}" subtitle="{{ __('Kelola data cabang dan konfigurasi geofencing.') }}">
        <div class="flex flex-wrap items-center gap-3">
            @if($canManage)
            <a href="{{ route('master-data.branches.create') }}" wire:navigate>
                <x-button variant="primary" icon="add">{{ __('Tambah Cabang') }}</x-button>
            </a>
            @endif
            <div class="min-w-[200px] flex-1">
                <input type="search" wire:model.live.debounce.250ms="search" placeholder="{{ __('Cari cabang...') }}" class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-4 text-sm text-ink outline-none" />
            </div>
        </div>

        @if($branches->count())
        <x-simple-table :headers="[__('Nama'), __('Kantor Pusat'), __('Status'), __('Geofence'), __('Aksi')]">
            @foreach($branches as $b)
            <tr>
                <td class="px-4 py-3 font-medium text-ink">{{ $b->name }}</td>
                <td class="px-4 py-3">
                    @if($b->is_main)
                    <span class="material-symbols-outlined text-lg text-amber-500">stars</span>
                    @else
                    <span class="text-xs text-on-surface-variant/40">-</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $b->is_active ? 'bg-success/10 text-success' : 'bg-error/10 text-error' }}">
                        {{ $b->is_active ? __('Aktif') : __('Nonaktif') }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    @if($b->latitude && $b->longitude)
                    <span class="text-xs text-on-surface-variant">{{ number_format($b->latitude,4) }}, {{ number_format($b->longitude,4) }} @if($b->radius) &middot; {{ $b->radius }}m @endif</span>
                    @else
                    <span class="text-xs text-on-surface-variant/50">-</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($canManage)
                    <div class="flex gap-1">
                        <a href="{{ route('master-data.branches.edit', $b) }}" wire:navigate>
                            <x-button variant="ghost" size="sm">{{ __('Edit') }}</x-button>
                        </a>
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

    @if($confirmingDeletion)
    <div class="fixed inset-0 z-[90] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <form wire:submit="delete" class="w-full max-w-sm rounded-xl bg-canvas p-6 shadow-xl text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-error/10"><span class="material-symbols-outlined text-3xl text-error">warning</span></div>
            <h3 class="text-lg font-semibold text-ink">{{ __('Hapus Cabang') }}</h3>
            <p class="mt-2 text-sm text-on-surface-variant">{{ __('Hapus cabang') }} <strong>{{ $deleteName }}</strong>? {{ __('Data departemen dan pegawai harus dipindahkan terlebih dahulu.') }}</p>
            <div class="mt-6 flex justify-center gap-3">
                <x-button variant="secondary" wire:click="$set('confirmingDeletion', false)">{{ __('Batal') }}</x-button>
                <x-button variant="danger" type="submit">{{ __('Hapus') }}</x-button>
            </div>
        </form>
    </div>
    @endif
</div>
