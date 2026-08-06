@php
    $color = $iconColor ?? 'blue';
    $iconClasses = [
        'green' => 'bg-primary-100 text-primary-700',
        'blue' => 'bg-sky-100 text-sky-700',
    ][$color] ?? 'bg-slate-100 text-slate-700';
@endphp

<div x-data="locationCard('{{ $mapId }}')"
     x-init="init()"
     {{ $attributes->merge(['class' => 'location-card-surface relative overflow-visible']) }}>
    <div class="relative z-10 mb-3 flex items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            @if (isset($icon))
                <div class="location-card-icon {{ $iconClasses }}">
                    <x-heroicon-o-map-pin class="h-5 w-5" />
                </div>
            @endif
            <h3 class="truncate text-sm font-semibold text-slate-950">{{ $title }}</h3>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @if ($showRefresh ?? false)
                <button x-on:click="refreshLocation()" title="{{ __('Refresh Location') }}" aria-label="{{ __('Refresh Location') }}"
                    class="location-icon-button">
                    <x-heroicon-o-arrow-path class="h-4 w-4" />
                </button>
            @endif
            <button x-on:click="toggle()"
                aria-label="{{ __('Toggle map') }}"
                class="location-map-toggle">
                <x-heroicon-o-chevron-down class="h-3.5 w-3.5 transition-transform duration-300"
                    x-bind:class="mapVisible ? 'rotate-180' : ''" />
                <span x-text="mapVisible ? '{{ __('Tutup Peta') }}' : '{{ __('Lihat Peta') }}'"></span>
            </button>
        </div>
    </div>

    <div class="relative z-10">
        <template x-if="lat && lng">
            <div class="flex items-center gap-2 mt-1">
                <a href="#"
                   x-on:click.prevent="window.open(`https://www.google.com/maps?q=${lat},${lng}`)"
                   class="location-coordinate-link">
                    <x-heroicon-o-map-pin class="h-3.5 w-3.5 shrink-0 text-primary-500" />
                    <span x-text="lat.toFixed(6) + ', ' + lng.toFixed(6)"></span>
                </a>
                {{-- Distance to office --}}
                <template x-if="officeDistance !== null && branchLng">
                    <span class="text-xs text-slate-400">
                        <span class="mx-1">•</span>
                        <span x-text="formattedDistance"></span>
                        <span>{{ __('from office') }}</span>
                    </span>
                </template>
            </div>
        </template>
        <template x-if="!lat || !lng">
            <span class="mt-1 block text-xs font-medium text-slate-500">
                {{ __('No location data') }}
            </span>
        </template>
        <div x-show="lastUpdated" class="mt-1 text-[10px] text-slate-400" x-text="lastUpdated"></div>
    </div>

    {{-- Collapsible Map Container --}}
    <div x-show="mapVisible"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="map-container relative z-10 mt-4 overflow-hidden rounded-xl border border-slate-200 shadow-inner"
         x-ref="mapContainer"
         style="height: 300px;"
         id="{{ $mapId }}"></div>

    {{-- Hidden coordinate update trigger from Livewire --}}
    <div x-on:gps-coordinates-updated.window="onCoordsUpdated($event.detail)"
         aria-hidden="true"
         class="hidden"></div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('locationCard', (mapId) => ({
            lat: null,
            lng: null,
            mapVisible: false,
            lastUpdated: '',
            _map: null,
            _userMarker: null,
            _officeMarker: null,
            _distanceLine: null,
            _geofenceCircle: null,
            officeDistance: null,
            branchLat: null,
            branchLng: null,
            branchRadius: null,
            branchName: '',

            init() {
                // Grab initial values from Livewire props
                this.lat = this.safeFloat(this.$wire.latitude);
                this.lng = this.safeFloat(this.$wire.longitude);
                this.branchLat = this.safeFloat(this.$wire.branchLatitude);
                this.branchLng = this.safeFloat(this.$wire.branchLongitude);
                this.branchRadius = this.safeInt(this.$wire.branchRadius);
                this.branchName = this.$wire.branchName || '';
                this.calcDistance();

                // Watch Livewire props for reactive updates.
                // NB: pakai $wire.$watch (Livewire), bukan Alpine $watch('$wire.x') yang tidak pernah fire.
                this.$wire.$watch('latitude', (val) => {
                    const parsed = this.safeFloat(val);
                    if (parsed !== null && parsed !== this.lat) {
                        this.lat = parsed;
                        this.calcDistance();
                        this.updateMap();
                    }
                });
                this.$wire.$watch('longitude', (val) => {
                    const parsed = this.safeFloat(val);
                    if (parsed !== null && parsed !== this.lng) {
                        this.lng = parsed;
                        this.calcDistance();
                        this.updateMap();
                    }
                });
            },

            safeFloat(val) {
                if (val === null || val === undefined || val === '') return null;
                const parsed = parseFloat(val);
                return isNaN(parsed) ? null : parsed;
            },

            safeInt(val) {
                if (val === null || val === undefined || val === '') return null;
                const parsed = parseInt(val, 10);
                return isNaN(parsed) ? null : parsed;
            },

            /**
             * Haversine distance between user and office in meters
             */
            calcDistance() {
                if (!this.lat || !this.lng || !this.branchLat || !this.branchLng) {
                    this.officeDistance = null;
                    return;
                }
                const R = 6371000;
                const dLat = this.toRad(this.branchLat - this.lat);
                const dLng = this.toRad(this.branchLng - this.lng);
                const a = Math.sin(dLat / 2) ** 2 +
                          Math.cos(this.toRad(this.lat)) * Math.cos(this.toRad(this.branchLat)) *
                          Math.sin(dLng / 2) ** 2;
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                this.officeDistance = Math.round(R * c);
            },

            toRad(deg) {
                return deg * (Math.PI / 180);
            },

            get formattedDistance() {
                if (this.officeDistance === null) return '';
                if (this.officeDistance < 1000) {
                    return this.officeDistance + ' m';
                }
                return (this.officeDistance / 1000).toFixed(1) + ' km';
            },

            toggle() {
                this.mapVisible = !this.mapVisible;
                if (this.mapVisible && this.lat && this.lng) {
                    this.$nextTick(() => this.initMap());
                }
            },

            initMap() {
                const container = this.$refs.mapContainer;
                if (!container || this._map) return;
                if (typeof L === 'undefined') return;

                // Compute bounds to fit both user and office
                const bounds = [];
                if (this.lat && this.lng) bounds.push([this.lat, this.lng]);
                if (this.branchLat && this.branchLng) bounds.push([this.branchLat, this.branchLng]);

                if (bounds.length < 2) {
                    // Only user location — show default view
                    this._map = L.map(container, { zoomControl: true }).setView(
                        [this.lat || -6.2, this.lng || 106.8], 15
                    );
                } else {
                    this._map = L.map(container, { zoomControl: true }).fitBounds(bounds, {
                        padding: [50, 50],
                        maxZoom: 16,
                    });
                }

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://openstreetmap.org/copyright">OSM</a>',
                }).addTo(this._map);

                // Custom icon for user (green marker)
                const userIcon = L.divIcon({
                    className: '',
                    html: `<div style="background:var(--color-success);color:var(--color-surface);width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid var(--color-surface);box-shadow:0 2px 8px rgba(0,0,0,0.3);font-size:16px;">📍</div>`,
                    iconSize: [32, 32],
                    iconAnchor: [16, 16],
                });

                this._userMarker = L.marker([this.lat, this.lng], { icon: userIcon })
                    .addTo(this._map)
                    .bindPopup(`<b>{{ __('You') }}</b><br>${this.lat.toFixed(6)}, ${this.lng.toFixed(6)}`);

                // Office marker (if available)
                if (this.branchLat && this.branchLng) {
                    const officeIcon = L.divIcon({
                        className: '',
                        html: `<div style="background:var(--color-module-hr);color:var(--color-surface);width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid var(--color-surface);box-shadow:0 2px 8px rgba(0,0,0,0.3);font-size:16px;">🏢</div>`,
                        iconSize: [32, 32],
                        iconAnchor: [16, 16],
                    });

                    const officeName = this.branchName || '{{ __('Office') }}';
                    this._officeMarker = L.marker([this.branchLat, this.branchLng], { icon: officeIcon })
                        .addTo(this._map)
                        .bindPopup(`<b>${officeName}</b><br>${this.branchLat.toFixed(6)}, ${this.branchLng.toFixed(6)}`);

                    // Distance line
                    this._distanceLine = L.polyline([
                        [this.lat, this.lng],
                        [this.branchLat, this.branchLng]
                    ], {
                        color: 'var(--color-muted)',
                        weight: 2,
                        dashArray: '8, 6',
                        opacity: 0.7,
                    }).addTo(this._map);

                    // Distance label at midpoint
                    if (this.officeDistance !== null) {
                        const midLat = (this.lat + this.branchLat) / 2;
                        const midLng = (this.lng + this.branchLng) / 2;
                        L.marker([midLat, midLng], {
                            icon: L.divIcon({
                                className: '',
                                html: `<div style="background:var(--color-surface);color:var(--color-primary-700);padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;border:1px solid var(--color-primary-200);box-shadow:0 1px 4px rgba(0,0,0,0.1);white-space:nowrap;">${this.formattedDistance}</div>`,
                                iconSize: [0, 0],
                                iconAnchor: [0, 0],
                            }),
                            interactive: false,
                        }).addTo(this._map);
                    }

                    // Geofence circle around office
                    if (this.branchRadius) {
                        this._geofenceCircle = L.circle([this.branchLat, this.branchLng], {
                            radius: this.branchRadius,
                            color: 'var(--color-module-hr)',
                            fillColor: 'var(--color-module-hr)',
                            fillOpacity: 0.06,
                            weight: 1.5,
                            dashArray: '4, 4',
                            opacity: 0.5,
                        }).addTo(this._map);

                        // Check if user is within geofence
                        if (this.officeDistance !== null && this.officeDistance <= this.branchRadius) {
                            this._geofenceCircle.setStyle({ color: 'var(--color-success)', fillColor: 'var(--color-success)' });
                        }
                    }
                }

                setTimeout(() => this._map.invalidateSize(), 200);
            },

            updateMap() {
                if (!this._map) {
                    if (this.mapVisible && this.lat && this.lng) {
                        this.$nextTick(() => this.initMap());
                    }
                    return;
                }

                // Update user marker position
                if (this._userMarker) {
                    this._userMarker.setLatLng([this.lat, this.lng]);
                    this._userMarker.setPopupContent(`<b>{{ __('You') }}</b><br>${this.lat.toFixed(6)}, ${this.lng.toFixed(6)}`);
                }

                // Update distance line
                if (this._distanceLine && this.branchLat && this.branchLng) {
                    this._distanceLine.setLatLngs([
                        [this.lat, this.lng],
                        [this.branchLat, this.branchLng]
                    ]);
                }

                // Recenter to show both points
                if (this.branchLat && this.branchLng) {
                    const bounds = [[this.lat, this.lng], [this.branchLat, this.branchLng]];
                    this._map.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 });
                } else {
                    this._map.setView([this.lat, this.lng], 15);
                }

                this.lastUpdated = '{{ __('Updated') }} ' + new Date().toLocaleTimeString('id-ID');
            },

            onCoordsUpdated(detail) {
                if (detail?.latitude) {
                    this.lat = this.safeFloat(detail.latitude);
                    this.lng = this.safeFloat(detail.longitude);
                    this.calcDistance();
                    this.updateMap();
                }
            },

            refreshLocation() {
                const rootEl = this.$el.closest('[x-data]');
                if (rootEl && rootEl.__x) {
                    const parentData = rootEl.__x.getData();
                    if (typeof parentData.captureGps === 'function') {
                        parentData.captureGps();
                    }
                }
            },
        }));
    });
</script>
@endpush
