@props(['padding' => true])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-outline-variant bg-canvas shadow-sm']) }}>
    {{ $slot }}
</div>
