@props(['align' => 'right', 'width' => '48'])

@php
$alignment = match ($align) {
    'left' => 'left-0 origin-top-left',
    'right' => 'right-0 origin-top-right',
    default => 'right-0 origin-top-right',
};

$widthClass = match ($width) {
    '48' => 'w-48',
    '56' => 'w-56',
    '64' => 'w-64',
    default => 'w-48',
};
@endphp

<div x-data="{ open: false }" class="relative inline-block text-left" @click.away="open = false" @keydown.escape.window="open = false">
    <button type="button" @click.stop="open = !open" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-ink transition-colors">
        {{ $trigger }}
    </button>

    <div x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="{{ $alignment }} absolute z-50 mt-2 {{ $widthClass }} origin-top-right rounded-xl border border-outline-variant bg-canvas shadow-lg ring-1 ring-black/5 overflow-hidden">
        <div class="py-1">
            {{ $slot }}
        </div>
    </div>
</div>
