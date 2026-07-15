@props([
    'sidebar' => false,
])

@if($sidebar)
    <a {{ $attributes->merge(['class' => 'flex items-center gap-2 px-0 py-1']) }}>
        <div class="flex aspect-square size-8 items-center justify-center rounded-xl bg-primary text-on-primary">
            <x-app-logo-icon class="size-5 fill-current text-on-primary" />
        </div>
        <span class="font-display text-base font-semibold text-ink">HRConnect</span>
    </a>
@else
    <a {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
        <div class="flex aspect-square size-8 items-center justify-center rounded-xl bg-primary text-on-primary">
            <x-app-logo-icon class="size-5 fill-current text-on-primary" />
        </div>
        <span class="font-display text-base font-semibold text-ink">HRConnect</span>
    </a>
@endif