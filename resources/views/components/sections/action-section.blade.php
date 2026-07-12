@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-outline-variant/50 bg-canvas shadow-sm']) }}>
    @if (isset($title) || isset($icon))
        <div class="border-b border-outline-variant/50 bg-surface-dim/30 px-5 py-4">
            <div class="flex items-center gap-3">
                @if (isset($icon))
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-outline-variant/50 bg-surface-dim text-primary shadow-sm">
                        {{ $icon }}
                    </div>
                @endif
                <div>
                    <h3 class="text-base font-semibold text-ink">{{ $title }}</h3>
                    @if ($description)
                        <p class="text-sm text-on-surface-variant">{{ $description }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="px-5 py-5">
        {{ $slot }}
    </div>

    @if (isset($actions))
        <div class="flex items-center justify-end gap-3 border-t border-outline-variant/50 px-5 py-4">
            {{ $actions }}
        </div>
    @endif
</div>
