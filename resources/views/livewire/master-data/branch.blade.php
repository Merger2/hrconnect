<x-layouts::app.sidebar :title="__('Cabang')">
    <div x-data="branchIndex()">
        <x-page-shell title="{{ __('Cabang') }}" subtitle="{{ __('Kelola data cabang dan konfigurasi geofencing.') }}">
            @can('manage_branches')
            <x-slot:actions>
                <x-button variant="primary" icon="add" @click="openCreate()">{{ __('Tambah Cabang') }}</x-button>
            </x-slot:actions>
            @endcan

            <x-slot:toolbar>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-on-surface-variant/40">
                        <span class="material-symbols-outlined text-lg">search</span>
                    </span>
                    <input type="search" placeholder="{{ __('Cari cabang...') }}" x-model="search" @input.debounce.300ms="page = 1; fetchBranches()"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas pl-10 pr-4 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink" />
                </div>
            </x-slot:toolbar>

            <div x-show="loading" class="py-16"><x-loading-skeleton mode="table" :rows="5" :cols="4" /></div>

            <template x-if="!loading && branches.length > 0">
                <div>
                    <x-simple-table :headers="[__('Nama'), __('Alamat'), __('Geofence'), __('Aksi')]" class="hidden lg:block">
                        <template x-for="b in branches" :key="b.id">
                            <tr class="transition-colors hover:bg-surface-dim/30">
                                <td class="px-4 py-3"><span class="font-medium text-ink" x-text="b.name"></span></td>
                                <td class="px-4 py-3"><span class="text-sm text-on-surface-variant" x-text="(b.address || '-').substring(0, 40)"></span></td>
                                <td class="px-4 py-3">
                                    <template x-if="hasGeo(b)">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 text-xs font-medium text-success ring-1 ring-inset ring-success/20">
                                            <span class="material-symbols-outlined text-sm">location_on</span>
                                            <span x-text="parseFloat(b.latitude).toFixed(4) + ', ' + parseFloat(b.longitude).toFixed(4)"></span>
                                        </span>
                                        <span class="ml-2 text-xs text-on-surface-variant" x-text="b.radius ? b.radius + 'm' : '-'"></span>
                                    </template>
                                    <template x-if="!hasGeo(b)">
                                        <span class="text-xs text-on-surface-variant/50">{{ __('Belum dikonfigurasi') }}</span>
                                    </template>
                                </td>
                                <td class="px-4 py-3">
                                    @can('manage_branches')
                                    <div class="flex gap-1">
                                        <x-button variant="ghost" size="sm" icon="edit" @click="openEdit(b)">{{ __('Edit') }}</x-button>
                                        <x-button variant="ghost" size="sm" icon="delete" @click="confirmDelete(b)" class="text-error">{{ __('Hapus') }}</x-button>
                                    </div>
                                    @endcan
                                </td>
                            </tr>
                        </template>
                    </x-simple-table>

                    <div class="space-y-3 lg:hidden">
                        <template x-for="b in branches" :key="b.id">
                            <div class="rounded-xl border border-outline-variant bg-canvas p-4 shadow-sm">
                                <h3 class="font-medium text-ink" x-text="b.name"></h3>
                                <p class="mt-1 text-sm text-on-surface-variant" x-text="(b.address || 'Tanpa alamat').substring(0, 60)"></p>
                                <template x-if="hasGeo(b)">
                                    <p class="mt-1 text-xs text-success">
                                        <span class="material-symbols-outlined align-middle text-sm">location_on</span>
                                        <span x-text="parseFloat(b.latitude).toFixed(4) + ', ' + parseFloat(b.longitude).toFixed(4)"></span>
                                        <span x-show="b.radius" x-text="' · ' + b.radius + 'm'"></span>
                                    </p>
                                </template>
                                @can('manage_branches')
                                <div class="mt-3 flex justify-end gap-2 border-t border-outline-variant/50 pt-3">
                                    <x-button variant="secondary" size="sm" icon="edit" @click="openEdit(b)">{{ __('Edit') }}</x-button>
                                    <x-button variant="secondary" size="sm" icon="delete" @click="confirmDelete(b)" class="text-error">{{ __('Hapus') }}</x-button>
                                </div>
                                @endcan
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 flex items-center justify-between rounded-lg border border-outline-variant/40 bg-surface-dim/30 px-4 py-2.5" x-show="lastPage > 1">
                        <button @click="page = Math.max(1, page - 1); fetchBranches()" :disabled="page <= 1"
                            class="flex items-center gap-1 rounded-lg border border-outline-variant/40 bg-canvas px-3 py-1.5 text-sm font-medium text-ink disabled:opacity-40">
                            <span class="material-symbols-outlined text-lg">chevron_left</span> {{ __('Sebelumnya') }}
                        </button>
                        <span class="text-sm text-on-surface-variant">{{ __('Halaman') }} <span x-text="page"></span> / <span x-text="lastPage"></span> (<span x-text="total"></span>)</span>
                        <button @click="page = Math.min(lastPage, page + 1); fetchBranches()" :disabled="page >= lastPage"
                            class="flex items-center gap-1 rounded-lg border border-outline-variant/40 bg-canvas px-3 py-1.5 text-sm font-medium text-ink disabled:opacity-40">
                            {{ __('Selanjutnya') }} <span class="material-symbols-outlined text-lg">chevron_right</span>
                        </button>
                    </div>
                </div>
            </template>

            <template x-if="!loading && branches.length === 0">
                <x-empty-state :title="__('Belum ada cabang')" :description="__('Tambahkan cabang perusahaan untuk memulai.')">
                    @can('manage_branches')
                    <x-slot:actions>
                        <x-button variant="primary" icon="add" @click="openCreate()">{{ __('Tambah Cabang') }}</x-button>
                    </x-slot:actions>
                    @endcan
                </x-empty-state>
            </template>
        </x-page-shell>

        {{-- Create Modal --}}
        <template x-if="creating">
            <div x-data="branchMap({ lat: parseFloat(form.latitude) || -2.5, lng: parseFloat(form.longitude) || 118, radius: parseInt(form.radius) || 100 })"
                x-init="$nextTick(() => initMap())"
                @keydown.escape.window="creating = false"
                class="jetstream-modal fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6"
                role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="creating = false" aria-hidden="true"></div>
                <div class="relative z-10 mx-auto w-full max-w-lg overflow-hidden rounded-xl bg-canvas shadow-xl"
                    style="max-height: calc(100dvh - 2rem)" @click.stop>
                    <div class="flex items-center justify-between border-b border-outline-variant/50 px-6 py-4">
                        <h2 class="text-lg font-semibold text-ink">{{ __('Tambah Cabang') }}</h2>
                        <button @click="creating = false" class="rounded-xl p-1.5 text-on-surface-variant hover:text-ink hover:bg-surface-container-high">
                            <span class="material-symbols-outlined text-lg">close</span>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div x-show="error" class="rounded-xl bg-error/10 px-4 py-3 text-sm text-error" x-text="error"></div>

                        <div><label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Nama Cabang') }} *</label><input x-model="form.name" class="w-full rounded-xl border bg-canvas px-3 py-2 text-sm text-ink" :class="!form.name.trim() && error ? 'border-error' : 'border-outline-variant'" required /></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Alamat') }}</label><input x-model="form.address" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink" /></div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-medium text-ink">{{ __('Lokasi (klik peta)') }}</span>
                                <button @click="locateMe()" class="rounded-lg px-2 py-1 text-xs font-medium text-ink hover:bg-surface-dim">
                                    <span class="material-symbols-outlined align-middle text-sm">my_location</span> {{ __('Lokasi Saya') }}
                                </button>
                            </div>
                            <div x-ref="map" class="h-[280px] w-full rounded-xl border border-outline-variant bg-surface-dim/30"></div>
                            <p class="mt-1 text-xs text-on-surface-variant"><span x-text="'Lat: ' + lat.toFixed(6)"></span> · <span x-text="'Lng: ' + lng.toFixed(6)"></span></p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Latitude') }}</label><input x-model="form.latitude" type="number" step="any" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink" /></div>
                            <div><label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Longitude') }}</label><input x-model="form.longitude" type="number" step="any" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink" /></div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Radius (meter)') }}</label>
                            <input x-model="form.radius" type="number" min="10" max="5000" placeholder="100" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink" />
                            <p class="mt-1 text-xs text-on-surface-variant">{{ __('Radius geofence. Default 100m.') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-outline-variant/50 bg-surface-dim/30 px-6 py-4">
                        <x-button variant="secondary" @click="creating = false; error = ''">{{ __('Batal') }}</x-button>
                        <x-button variant="primary" @click="save()" x-bind:disabled="saving">
                            <span x-show="!saving">{{ __('Simpan') }}</span>
                            <span x-show="saving" class="material-symbols-outlined animate-spin">progress_activity</span>
                        </x-button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Edit Modal --}}
        <template x-if="editing">
            <div x-data="branchMap({ lat: parseFloat(form.latitude) || -2.5, lng: parseFloat(form.longitude) || 118, radius: parseInt(form.radius) || 100 })"
                x-init="$nextTick(() => initMap())"
                @keydown.escape.window="editing = false"
                class="jetstream-modal fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6"
                role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="editing = false" aria-hidden="true"></div>
                <div class="relative z-10 mx-auto w-full max-w-lg overflow-hidden rounded-xl bg-canvas shadow-xl"
                    style="max-height: calc(100dvh - 2rem)" @click.stop>
                    <div class="flex items-center justify-between border-b border-outline-variant/50 px-6 py-4">
                        <h2 class="text-lg font-semibold text-ink">{{ __('Edit Cabang') }}</h2>
                        <button @click="editing = false" class="rounded-xl p-1.5 text-on-surface-variant hover:text-ink hover:bg-surface-container-high">
                            <span class="material-symbols-outlined text-lg">close</span>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div x-show="error" class="rounded-xl bg-error/10 px-4 py-3 text-sm text-error" x-text="error"></div>

                        <div><label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Nama Cabang') }} *</label><input x-model="form.name" class="w-full rounded-xl border bg-canvas px-3 py-2 text-sm text-ink" :class="!form.name.trim() && error ? 'border-error' : 'border-outline-variant'" required /></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Alamat') }}</label><input x-model="form.address" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink" /></div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-medium text-ink">{{ __('Lokasi (klik peta)') }}</span>
                                <button @click="locateMe()" class="rounded-lg px-2 py-1 text-xs font-medium text-ink hover:bg-surface-dim">
                                    <span class="material-symbols-outlined align-middle text-sm">my_location</span> {{ __('Lokasi Saya') }}
                                </button>
                            </div>
                            <div x-ref="map" class="h-[280px] w-full rounded-xl border border-outline-variant bg-surface-dim/30"></div>
                            <p class="mt-1 text-xs text-on-surface-variant"><span x-text="'Lat: ' + lat.toFixed(6)"></span> · <span x-text="'Lng: ' + lng.toFixed(6)"></span></p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Latitude') }}</label><input x-model="form.latitude" type="number" step="any" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink" /></div>
                            <div><label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Longitude') }}</label><input x-model="form.longitude" type="number" step="any" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink" /></div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Radius (meter)') }}</label>
                            <input x-model="form.radius" type="number" min="10" max="5000" placeholder="100" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink" />
                            <p class="mt-1 text-xs text-on-surface-variant">{{ __('Radius geofence. Default 100m.') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-outline-variant/50 bg-surface-dim/30 px-6 py-4">
                        <x-button variant="secondary" @click="editing = false">{{ __('Batal') }}</x-button>
                        <x-button variant="primary" @click="save()">{{ __('Perbarui') }}</x-button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('branchMap', ({ lat, lng, radius }) => ({
            lat, lng, radius, map: null, marker: null, circle: null,
            initMap() {
                if (!window.L || !this.$refs.map) return;
                const map = window.L.map(this.$refs.map).setView([this.lat, this.lng], 15);
                window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OSM' }).addTo(map);
                this.map = map;
                this.marker = window.L.marker([this.lat, this.lng], { draggable: true }).addTo(map);
                this.circle = window.L.circle([this.lat, this.lng], { radius: this.radius, color: '#0a0a0a', fillColor: '#0a0a0a', fillOpacity: 0.08, weight: 1 }).addTo(map);
                map.on('click', e => { this.lat = e.latlng.lat; this.lng = e.latlng.lng; this.sync(); });
                this.marker.on('dragend', () => { const p = this.marker.getLatLng(); this.lat = p.lat; this.lng = p.lng; this.sync(); });
                this.$watch('radius', () => { if (this.circle && this.marker) { this.circle.setLatLng(this.marker.getLatLng()); this.circle.setRadius(parseInt(this.radius) || 100); } });
            },
            locateMe() {
                if (!navigator.geolocation) return;
                navigator.geolocation.getCurrentPosition(pos => {
                    this.lat = pos.coords.latitude;
                    this.lng = pos.coords.longitude;
                    if (this.map) this.map.setView([this.lat, this.lng], 17);
                    if (this.marker) this.marker.setLatLng([this.lat, this.lng]);
                    if (this.circle) { this.circle.setLatLng([this.lat, this.lng]); this.circle.setRadius(parseInt(this.radius) || 100); }
                    this.sync();
                }, null, { enableHighAccuracy: true });
            },
            sync() {
                const el = this.$el.closest('[x-data="branchIndex()"]')?.__x?.$data;
                if (el?.form) { el.form.latitude = String(this.lat); el.form.longitude = String(this.lng); }
            },
        }));
    });
    </script>
    @endpush
</x-layouts::app.sidebar>
