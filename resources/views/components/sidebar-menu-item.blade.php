@props([
    'item' => null,
    'label' => null,
    'route' => null,
    'icon' => null,
    'active' => false,
    'badge' => null,
])

@php
$label ??= $item['label'] ?? '';
$icon ??= $item['icon'] ?? null;
$route ??= $item['route'] ?? null;
$active ??= $item['active'] ?? false;
$badge ??= $item['badge'] ?? null;
$tooltip = $item['tooltip'] ?? $label;
@endphp

<a href="{{ $route ? route($route) : '#' }}"
   @class([
       'group flex h-11 items-center gap-3 rounded-lg border-l-[3px] px-3 text-sm transition-smooth',
       'border-primary bg-primary/5 font-semibold text-ink' => $active,
       'border-transparent font-medium text-on-surface-variant hover:border-outline hover:bg-surface-dim/30 hover:text-ink' => !$active,
   ])}
   wire:navigate
   title="{{ $tooltip }}">

    <span @class([
        'material-symbols-outlined text-xl transition-smooth',
        'text-primary' => $active,
        'text-on-surface-variant group-hover:text-ink' => !$active,
    ])>{{ $icon }}</span>

    <span class="flex-1 leading-5">{{ __($label) }}</span>

    @if ($badge !== null && $badge > 0)
        <span class="inline-flex min-w-[1.25rem] justify-center rounded-pill bg-error/15 px-1.5 py-0.5 text-caption-sm font-bold text-error">
            {{ $badge > 99 ? '99+' : $badge }}
        </span>
    @endif
</a>