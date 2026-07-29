@php
    $color = $iconColor ?? 'blue';
    $iconClasses = [
        'green' => 'bg-primary-100 text-primary-700 dark:bg-primary-900/45 dark:text-primary-200',
        'blue' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/45 dark:text-sky-200',
    ][$color] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200';
@endphp

<div {{ $attributes->merge(['class' => 'location-card-surface relative overflow-visible']) }}>
    <div class="relative z-10 mb-3 flex items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            @if (isset($icon))
                <div class="location-card-icon {{ $iconClasses }}">
                    <x-heroicon-o-map-pin class="h-5 w-5" />
                </div>
            @endif
            <h3 class="truncate text-sm font-semibold text-slate-950 dark:text-white">{{ $title }}</h3>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @if ($showRefresh ?? false)
                <button onclick="refreshLocation()" id="refresh-location-btn" title="{{ __('Refresh Location') }}" aria-label="{{ __('Refresh Location') }}"
                    class="location-icon-button">
                    <x-heroicon-o-arrow-path class="h-4 w-4" />
                </button>
            @endif
            <button onclick="toggleMap('{{ $mapId }}')" id="toggle-{{ $mapId }}-btn"
                aria-label="{{ __('Toggle map') }}"
                class="location-map-toggle">
                <x-heroicon-o-chevron-down class="h-3.5 w-3.5 transition-transform duration-300" />
                <span>{{ __('Show Map') }}</span>
            </button>
        </div>
    </div>

    <div id="location-text-{{ $mapId }}" class="relative z-10">
        @if ($latitude && $longitude)
            <div class="flex items-center gap-2 mt-1">
                <a href="#" onclick="window.openMap({{ $latitude }}, {{ $longitude }}); return false;"
                    class="location-coordinate-link">
                    <x-heroicon-o-map-pin class="h-3.5 w-3.5 shrink-0 text-primary-500" />
                    {{ $latitude . ', ' . $longitude }}
                </a>
            </div>
        @else
            <span class="mt-1 block text-xs font-medium text-slate-500 dark:text-slate-400">
                @if (isset($showRefresh) && $showRefresh)
                    {{ __('Detecting location...') }}
                @else
                    {{ __('No location data') }}
                @endif
            </span>
        @endif
        <div id="location-updated-{{ $mapId }}" class="mt-1 text-[10px] text-slate-400" wire:ignore></div>
    </div>

    {{-- Collapsible Map Container --}}
    <div class="map-container relative z-10 mt-4 hidden overflow-hidden rounded-xl border border-slate-200 shadow-inner dark:border-slate-800" id="{{ $mapId }}" style="height: 250px;" wire:ignore></div>
</div>

<script>
    function toggleMap(mapId) {
        const mapContainer = document.getElementById(mapId);
        const btn = document.getElementById('toggle-' + mapId + '-btn');
        const icon = btn.querySelector('svg');
        const text = btn.querySelector('span');
        
        if (mapContainer.classList.contains('hidden')) {
            // Show Map
            mapContainer.classList.remove('hidden');
            text.textContent = "{{ __('Tutup Peta') }}";
            icon.classList.add('rotate-180');
            
            // Trigger leaflet resize if needed
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 100);
            
            // Initialize map if function exists (handled by scan component usually)
            if (typeof initMap === 'function') {
                // initMap(mapId); // Might need specific logic depends on how maps are initialized
            }
        } else {
            // Hide Map
            mapContainer.classList.add('hidden');
            text.textContent = "{{ __('Lihat Peta') }}";
            icon.classList.remove('rotate-180');
        }
    }
</script>
