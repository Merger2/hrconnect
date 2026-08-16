@props([
    'tone' => 'neutral',
    'pill' => false,
])

@php
    $baseClass = $pill
        ? 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset'
        : 'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset';

    $toneClass = [
        'neutral' => 'bg-gray-50 text-gray-600 ring-gray-500/10',
        'primary' => 'bg-primary-50 text-primary-700 ring-primary-600/20',
        'info' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'success' => 'bg-green-50 text-green-700 ring-green-600/20',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'danger' => 'bg-red-50 text-red-700 ring-red-600/20',
        'accent' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
    ][$tone] ?? 'bg-gray-50 text-gray-600 ring-gray-500/10';
@endphp

<span {{ $attributes->merge(['class' => $baseClass . ' ' . $toneClass]) }}>
    {{ $slot }}
</span>
