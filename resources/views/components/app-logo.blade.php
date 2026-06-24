@props([
    'sidebar' => false,
])

@if($sidebar)
    <a {{ $attributes->merge(['class' => 'flex items-center gap-2 px-4 py-3']) }}>
        <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-ink text-white">
            <x-app-logo-icon class="size-5 fill-current text-white" />
        </div>
        <span class="font-display text-base font-medium text-ink">HRConnect</span>
    </a>
@else
    <a {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
        <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-ink text-white">
            <x-app-logo-icon class="size-5 fill-current text-white" />
        </div>
        <span class="font-display text-base font-medium text-ink">HRConnect</span>
    </a>
@endif
