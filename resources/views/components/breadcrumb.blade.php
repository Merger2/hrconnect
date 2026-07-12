@props([
    'items' => [],
    'separator' => 'chevron_right',
])

<nav aria-label="Breadcrumb" class="flex items-center gap-1 text-caption-md text-on-surface-variant">
    @foreach ($items as $i => $item)
        @php
            $isLast = $i === array_key_last($items);
            $url = $item['url'] ?? null;
            $label = $item['label'] ?? '';
            $icon = $item['icon'] ?? null;
        @endphp

        @if ($isLast)
            <span class="font-semibold text-ink" aria-current="page">
                @if ($icon)
                    <span class="material-symbols-outlined text-sm align-middle">{{ $icon }}</span>
                @endif
                {{ $label }}
            </span>
        @else
            @if ($url)
                <a href="{{ $url }}"
                   class="flex items-center gap-1 transition-smooth hover:text-primary"
                   wire:navigate>
                    @if ($icon)
                        <span class="material-symbols-outlined text-sm">{{ $icon }}</span>
                    @endif
                    {{ $label }}
                </a>
            @else
                <span class="flex items-center gap-1">
                    @if ($icon)
                        <span class="material-symbols-outlined text-sm">{{ $icon }}</span>
                    @endif
                    {{ $label }}
                </span>
            @endif

            <span class="material-symbols-outlined text-sm text-on-surface-variant/40">{{ $separator }}</span>
        @endif
    @endforeach
</nav>