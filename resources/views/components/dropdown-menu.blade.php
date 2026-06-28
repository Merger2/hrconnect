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

<div x-data="{ open: false }" class="relative">
    <div @@click="open = !open" class="cursor-pointer">
        {{ $trigger }}
    </div>

    <div x-show="open"
        @@clickaway="open = false"
        @@keydown.window.escape="open = false"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="{{ $alignment }} absolute z-50 mt-1 {{ $widthClass }} rounded-xl border border-outline-variant bg-canvas py-1 shadow-lg">
        {{ $slot }}
    </div>
</div>
