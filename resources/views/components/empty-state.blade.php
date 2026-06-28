@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'py-16 text-center']) }}>
    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-surface-container-high">
        @if (isset($icon))
            {{ $icon }}
        @else
            <span class="material-symbols-outlined text-3xl text-on-surface-variant/50">inbox</span>
        @endif
    </div>
    <p class="text-base font-semibold text-ink">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-sm text-on-surface-variant max-w-sm mx-auto">{{ $description }}</p>
    @endif
    @if (isset($actions))
        <div class="mt-6">{{ $actions }}</div>
    @endif
</div>
