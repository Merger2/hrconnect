@props([
    'title',
    'description' => null,
    'backHref' => null,
    'backLabel' => null,
])

<header {{ $attributes->merge(['class' => 'mb-6']) }}>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            @if ($backHref)
                <a href="{{ $backHref }}" aria-label="{{ $backLabel ?? __('Kembali') }}"
                    class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-on-surface-variant transition-colors hover:bg-surface-dim hover:text-ink"
                >
                    <span class="material-symbols-outlined text-xl">arrow_back</span>
                </a>
            @endif

            @if (isset($icon))
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-surface-dim text-primary">
                    {{ $icon }}
                </div>
            @endif

            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-semibold text-ink">{{ $title }}</h1>
                    @if (isset($meta))
                        <div>{{ $meta }}</div>
                    @endif
                </div>
                @if ($description)
                    <p class="mt-0.5 text-sm text-on-surface-variant">{{ $description }}</p>
                @endif
            </div>
        </div>

        @if (isset($actions))
            <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
        @endif
    </div>
</header>
