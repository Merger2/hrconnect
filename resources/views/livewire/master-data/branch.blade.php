<div>
    <x-page-shell title="{{ __('Cabang') }}" subtitle="{{ __('Kelola data cabang dan konfigurasi geofencing.') }}">
        @if($this->canManage())
        <x-slot:actions>
            <x-button variant="primary" icon="add" wire:click="showCreating">
                {{ __('Tambah Cabang') }}
            </x-button>
        </x-slot:actions>
        @endif

        <x-slot:toolbar>
            <div class="flex items-center gap-3">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-on-surface-variant/40">
                        <span class="material-symbols-outlined text-lg">search</span>
                    </span>
                    <input type="search" placeholder="{{ __('Cari cabang...') }}" wire:model.live.debounce.300ms="search"
                        class="h-10 w-full rounded-xl border border-outline-variant bg-canvas pl-10 pr-4 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink" />
                </div>
            </div>
        </x-slot:toolbar>

        @if($branches->count())
        {{-- Desktop --}}
        <x-simple-table :headers="[__('Nama'), __('Alamat'), __('Geofence'), __('Aksi')]" class="hidden lg:block">
            @foreach($branches as $branch)
            <tr class="transition-colors hover:bg-surface-dim/30">
                <td class="px-4 py-3">
                    <span class="font-medium text-ink">{{ $branch->name }}</span>
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

        {{-- Mobile --}}
        <div class="space-y-3 lg:hidden">
            @foreach($branches as $branch)
            <div class="rounded-xl border border-outline-variant bg-canvas p-4 shadow-sm">
                <h3 class="font-medium text-ink">{{ $branch->name }}</h3>
                <p class="mt-1 text-sm text-on-surface-variant">{{ $branch->address ? \Illuminate\Support\Str::limit($branch->address, 60) : __('Tanpa alamat') }}</p>
                @if($branch->latitude && $branch->longitude)
                <p class="mt-1 text-xs text-success">
                    <span class="material-symbols-outlined align-middle text-sm">location_on</span>
                    {{ number_format($branch->latitude, 4) }}, {{ number_format($branch->longitude, 4) }}
                    @if($branch->radius) · {{ $branch->radius }}m @endif
                </p>
                @else
                <p class="mt-1 text-xs text-on-surface-variant/50">{{ __('Geofence belum dikonfigurasi') }}</p>
                @endif
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
    <x-modal :show="$creating" :title="__('Tambah Cabang')" max-width="lg">
        <div>
            @if($creating)
            <div x-data="branchMapPicker({
                lat: @js($latitude ?? -2.5),
                lng: @js($longitude ?? 118),
                radius: @js((int) ($radius ?? 100)),
            })" x-init="initMap()">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-forms.input label="{{ __('Nama Cabang') }}" wire:model="name" required />
                    </div>
                    <div class="sm:col-span-2">
                        <x-forms.input label="{{ __('Alamat') }}" wire:model="address" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Lokasi (klik peta)') }}</label>
                        <div x-ref="map" class="h-[280px] w-full rounded-xl border border-outline-variant bg-surface-dim/30"></div>
                        <p class="mt-1 text-xs text-on-surface-variant">
                            <span x-text="'Lat: ' + lat.toFixed(6)"></span> ·
                            <span x-text="'Lng: ' + lng.toFixed(6)"></span>
                        </p>
                    </div>
                    <div>
                        <x-forms.input label="{{ __('Latitude') }}" wire:model="latitude" type="number" step="any" />
                    </div>
                    <div>
                        <x-forms.input label="{{ __('Longitude') }}" wire:model="longitude" type="number" step="any" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-forms.input label="{{ __('Radius (meter)') }}" wire:model="radius" type="number" min="10" max="5000" placeholder="100" />
                        <p class="mt-1 text-xs text-on-surface-variant">{{ __('Radius geofence untuk validasi absensi. Default 100m.') }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
        <x-slot:footer>
            <x-button variant="secondary" wire:click="$set('creating', false)">{{ __('Batal') }}</x-button>
            <x-button variant="primary" wire:click="create">{{ __('Simpan') }}</x-button>
        </x-slot:footer>
    </x-modal>

    {{-- Edit Modal --}}
    <x-modal :show="$editing" :title="__('Edit Cabang')" max-width="lg">
        <div>
            @if($editing)
            <div x-data="branchMapPicker({
                lat: @js($latitude ?? -2.5),
                lng: @js($longitude ?? 118),
                radius: @js((int) ($radius ?? 100)),
            })" x-init="initMap()">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-forms.input label="{{ __('Nama Cabang') }}" wire:model="name" required />
                    </div>
                    <div class="sm:col-span-2">
                        <x-forms.input label="{{ __('Alamat') }}" wire:model="address" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Lokasi (klik peta)') }}</label>
                        <div x-ref="map" class="h-[280px] w-full rounded-xl border border-outline-variant bg-surface-dim/30"></div>
                        <p class="mt-1 text-xs text-on-surface-variant">
                            <span x-text="'Lat: ' + lat.toFixed(6)"></span> ·
                            <span x-text="'Lng: ' + lng.toFixed(6)"></span>
                        </p>
                    </div>
                    <div>
                        <x-forms.input label="{{ __('Latitude') }}" wire:model="latitude" type="number" step="any" />
                    </div>
                    <div>
                        <x-forms.input label="{{ __('Longitude') }}" wire:model="longitude" type="number" step="any" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-forms.input label="{{ __('Radius (meter)') }}" wire:model="radius" type="number" min="10" max="5000" placeholder="100" />
                        <p class="mt-1 text-xs text-on-surface-variant">{{ __('Radius geofence untuk validasi absensi. Default 100m.') }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
        <x-slot:footer>
            <x-button variant="secondary" wire:click="$set('editing', false)">{{ __('Batal') }}</x-button>
            <x-button variant="primary" wire:click="update">{{ __('Perbarui') }}</x-button>
        </x-slot:footer>
    </x-modal>

    {{-- Delete --}}
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

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('branchMapPicker', ({ lat, lng, radius }) => ({
            lat, lng, radius,
            marker: null,
            circle: null,

            initMap() {
                this.$nextTick(() => {
                    if (!window.L || !this.$refs.map) return;

                    const map = window.L.map(this.$refs.map).setView([this.lat, this.lng], 15);
                    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap',
                    }).addTo(map);

                    this.marker = window.L.marker([this.lat, this.lng], { draggable: true }).addTo(map);
                    this.circle = window.L.circle([this.lat, this.lng], {
                        radius: this.radius,
                        color: '#0a0a0a',
                        fillColor: '#0a0a0a',
                        fillOpacity: 0.08,
                        weight: 1,
                    }).addTo(map);

                    map.on('click', (e) => {
                        this.lat = e.latlng.lat;
                        this.lng = e.latlng.lng;
                        this.sync();
                    });

                    this.marker.on('dragend', () => {
                        const pos = this.marker.getLatLng();
                        this.lat = pos.lat;
                        this.lng = pos.lng;
                        this.sync();
                    });

                    this.$watch('radius', () => this.updateCircle());
                    this.$watch('$wire.latitude', v => { if (v != null) { this.lat = parseFloat(v); this.updateMarker(); } });
                    this.$watch('$wire.longitude', v => { if (v != null) { this.lng = parseFloat(v); this.updateMarker(); } });
                });
            },

            sync() {
                this.updateMarker();
                this.updateCircle();
                this.$wire?.set('latitude', this.lat ? String(this.lat) : null);
                this.$wire?.set('longitude', this.lng ? String(this.lng) : null);
            },

            updateMarker() {
                if (!this.marker) return;
                const ll = window.L.latLng(parseFloat(this.lat) || 0, parseFloat(this.lng) || 0);
                this.marker.setLatLng(ll);
                this.updateCircle();
            },

            updateCircle() {
                if (!this.circle || !this.marker) return;
                this.circle.setLatLng(this.marker.getLatLng());
                this.circle.setRadius(parseInt(this.radius) || 100);
            },
        }));
    });
</script>
@endpush
