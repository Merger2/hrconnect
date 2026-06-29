@props([
    'title',
    'latitude' => null,
    'longitude' => null,
    'mapId' => 'location-map',
    'showRefresh' => false,
    'iconColor' => 'primary',
])

@php
$iconClasses = [
    'primary' => 'bg-primary/10 text-primary',
    'info' => 'bg-info/10 text-info',
    'success' => 'bg-success/10 text-success',
][$iconColor] ?? 'bg-primary/10 text-primary';
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-outline-variant/50 bg-canvas p-4 shadow-sm']) }}>
    <div class="mb-3 flex items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $iconClasses }}">
                <span class="material-symbols-outlined text-lg">location_on</span>
            </div>
            <h3 class="truncate text-sm font-semibold text-ink">{{ $title }}</h3>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @if ($showRefresh)
                <button type="button" onclick="refreshLocation()" id="refresh-location-btn"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-dim hover:text-ink">
                    <span class="material-symbols-outlined text-base">refresh</span>
                </button>
            @endif
            <button type="button" onclick="toggleLocationMap('{{ $mapId }}')" id="toggle-{{ $mapId }}-btn"
                class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-on-surface-variant hover:bg-surface-dim hover:text-ink">
                <span id="toggle-{{ $mapId }}-label">{{ __('Lihat Peta') }}</span>
                <span class="material-symbols-outlined text-sm transition-transform duration-300" id="toggle-{{ $mapId }}-icon">expand_more</span>
            </button>
        </div>
    </div>

    <div id="location-text-{{ $mapId }}">
        @if ($latitude && $longitude)
            <a href="#" onclick="window.openMap({{ $latitude }}, {{ $longitude }}); return false;"
                class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">
                <span class="material-symbols-outlined text-xs">location_on</span>
                {{ number_format($latitude, 6) }}, {{ number_format($longitude, 6) }}
            </a>
        @else
            <p class="text-xs text-on-surface-variant">{{ $showRefresh ? __('Mendeteksi lokasi...') : __('Tidak ada data lokasi') }}</p>
        @endif
    </div>

    <div id="{{ $mapId }}"
        class="mt-4 hidden overflow-hidden rounded-xl border border-outline-variant/50 shadow-inner"
        style="height: 250px;" wire:ignore>
    </div>
</div>

@push('scripts')
<script>
    window.toggleLocationMap = function(mapId) {
        const container = document.getElementById(mapId);
        const btn = document.getElementById('toggle-' + mapId + '-btn');
        const label = document.getElementById('toggle-' + mapId + '-label');
        const icon = document.getElementById('toggle-' + mapId + '-icon');

        if (!container) return;

        if (container.classList.contains('hidden')) {
            container.classList.remove('hidden');
            label.textContent = '{{ __('Tutup Peta') }}';
            icon.classList.add('rotate-180');
            setTimeout(() => { window.dispatchEvent(new Event('resize')); }, 100);
        } else {
            container.classList.add('hidden');
            label.textContent = '{{ __('Lihat Peta') }}';
            icon.classList.remove('rotate-180');
        }
    };

    window.openMap = function(lat, lng) {
        window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
    };

    window.initMap = function(mapId, lat, lng, popupText) {
        const container = document.getElementById(mapId);
        if (!container || container.dataset.mapInit) return;
        container.dataset.mapInit = 'true';

        const map = L.map(mapId).setView([lat, lng], 16);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 21,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        const marker = L.marker([lat, lng]).addTo(map);
        if (popupText) marker.bindPopup(popupText).openPopup();

        setTimeout(() => map.invalidateSize(), 200);
    };
</script>
@endpush
