@props(['title', 'description' => null, 'framed' => false])

@php
$baseClass = $framed
    ? 'mx-auto max-w-xl rounded-xl border border-outline-variant/50 bg-canvas p-6 text-center shadow-sm'
    : 'py-16 text-center';
@endphp

<div {{ $attributes->merge(['class' => $baseClass]) }}>
    <div @class([
        'mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full',
        'bg-surface-container-high' => !$framed,
        'bg-surface-dim' => $framed,
    ])>
        @if (isset($icon))
            {{ $icon }}
        @else
            <span class="material-symbols-outlined text-3xl text-on-surface-variant/50">inbox</span>
        @endif
    </div>
    <p class="text-base font-semibold text-ink">{{ $title }}</p>
    @if ($description)
        <p class="mx-auto mt-1 max-w-sm text-sm text-on-surface-variant">{{ $description }}</p>
    @endif
    @if (isset($actions))
        <div class="mt-6">{{ $actions }}</div>
    @endif
</div>
