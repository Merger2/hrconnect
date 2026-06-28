@props(['columns' => 3])

@php
$gridCols = match ($columns) {
    1 => 'grid-cols-1',
    2 => 'grid-cols-1 sm:grid-cols-2',
    3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
    default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
};
@endphp

<div {{ $attributes->merge(['class' => "grid gap-4 $gridCols"]) }}>
    {{ $slot }}
</div>
