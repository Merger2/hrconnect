@props(['title', 'subtitle' => null, 'actions' => null])

<div {{ $attributes->merge(['class' => 'space-y-6']) }}>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold text-ink">{{ $title }}</h1>
            @if ($subtitle)
                <p class="text-sm text-on-surface-variant">{{ $subtitle }}</p>
            @endif
        </div>
        @if (isset($actions))
            <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
        @endif
    </div>

    @if (isset($toolbar))
        <div>{{ $toolbar }}</div>
    @endif

    {{ $slot }}
</div>
