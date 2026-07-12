<div>
    <x-slot:title>{{ $branch?->exists ? __('Edit Cabang') : __('Tambah Cabang') }}</x-slot:title>

    <div class="mx-auto max-w-3xl space-y-6">
        <div class="rounded-xl bg-gradient-to-r from-primary-soft/60 to-transparent p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-primary to-primary-deep text-white shadow-soft">
                    <span class="material-symbols-outlined text-xl">{{ $branch?->exists ? 'edit' : 'add' }}</span>
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-ink">{{ $branch?->exists ? __('Edit Cabang') : __('Tambah Cabang') }}</h1>
                    <p class="mt-0.5 text-sm text-muted">{{ $branch?->exists ? $branch->name : __('Isi data cabang baru') }}</p>
                </div>
            </div>
        </div>

        <form wire:submit="save" class="rounded-xl border border-outline-variant/30 bg-canvas p-6 shadow-soft space-y-5">
            <div>
                <label for="company_id" class="mb-1 block text-sm font-medium text-ink">{{ __('Perusahaan') }} *</label>
                <x-forms.tom-select 
                    :options="$companies"
                    wire:model="companyId"
                    placeholder="{{ __('Pilih perusahaan') }}"
                    id="company_id"
                />
                @error('companyId')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-ink">{{ __('Nama Cabang') }} *</label>
                <input wire:model="name" required class="mt-1.5 w-full rounded-md border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink" placeholder="{{ __('Kantor Pusat, Cabang Bandung, dsb') }}" />
                @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink">{{ __('Alamat') }}</label>
                <textarea wire:model="address" id="address" rows="2" class="mt-1.5 w-full rounded-md border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink outline-none placeholder:text-muted-soft focus:border-ink focus:ring-1 focus:ring-ink" placeholder="{{ __('Jl. Merdeka No. 123, Jakarta') }}"></textarea>
                @error('address')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <input type="hidden" wire:model="isMain" value="0">
                    <input type="checkbox" wire:model="isMain" value="1" id="branch-is-main" class="rounded border-outline-variant">
                    <label for="branch-is-main" class="text-sm font-medium text-ink">{{ __('Kantor Pusat') }}</label>
                </div>
                <p class="text-xs text-on-surface-variant/60">{{ __('Hanya satu kantor pusat per perusahaan.') }}</p>
            </div>

            <div wire:ignore x-data="{ init: false }" x-init="
                $nextTick(() => {
                    const latEl = document.getElementById('lat-input');
                    const lngEl = document.getElementById('lng-input');
                    if (!latEl || !lngEl) return;
                    if (window._branchMapRef) { window._branchMapRef.remove(); window._branchMapRef = null; window._branchMarker = null; }
                    window.initializeMap({
                        location: (latEl.value && lngEl.value) ? [parseFloat(latEl.value), parseFloat(lngEl.value)] : undefined,
                        onUpdate: (lat, lng) => {
                            latEl.value = lat; latEl.dispatchEvent(new Event('input', { bubbles: true }));
                            lngEl.value = lng; lngEl.dispatchEvent(new Event('input', { bubbles: true }));
                        },
                    });
                    [latEl, lngEl].forEach(el => {
                        el.addEventListener('input', () => {
                            const lat = parseFloat(latEl.value);
                            const lng = parseFloat(lngEl.value);
                            if (!isNaN(lat) && !isNaN(lng) && window._branchMapRef) {
                                window._branchMapRef.setView([lat, lng], 13);
                            }
                        });
                    });
                    if (!latEl.value && !lngEl.value) {
                        setTimeout(() => window.detectLocation(), 1000);
                    }
                })
            " class="h-64 w-full overflow-hidden rounded-xl border border-outline-variant"><div id="branch-map" class="h-full w-full"></div></div>
            <button type="button" @click="window.detectLocation()" class="text-sm text-primary underline">{{ __('Deteksi lokasi saya') }}</button>

            <div class="grid grid-cols-4 gap-3">
                <div class="col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Latitude') }}</label>
                    <input id="lat-input" wire:model="latitude" type="number" step="any" class="mt-1.5 w-full rounded-md border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink" />
                </div>
                <div class="col-span-2">
                    <label class="mb-1 block text-sm font-medium text-ink">{{ __('Longitude') }}</label>
                    <input id="lng-input" wire:model="longitude" type="number" step="any" class="mt-1.5 w-full rounded-md border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink" />
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-ink">{{ __('Radius Geofence (meter)') }}</label>
                <input wire:model="radius" type="number" min="10" class="mt-1.5 w-full rounded-md border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink outline-none focus:border-ink focus:ring-1 focus:ring-ink" placeholder="100" />
            </div>

            <div class="flex items-center gap-2">
                <input type="hidden" wire:model="isActive" value="0">
                <input type="checkbox" wire:model="isActive" value="1" id="branch-is-active" class="rounded border-outline-variant">
                <label for="branch-is-active" class="text-sm font-medium text-ink">{{ __('Cabang Aktif') }}</label>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-outline-variant/30 pt-5">
                <a href="{{ route('master-data.branches') }}" wire:navigate>
                    <x-button variant="secondary" icon="close">{{ __('Batal') }}</x-button>
                </a>
                <x-button variant="primary" type="submit">{{ $branch?->exists ? __('Perbarui') : __('Simpan') }}</x-button>
            </div>
        </form>
    </div>
</div>
