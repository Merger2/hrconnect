@props(['submit' => null])

<div {{ $attributes->merge(['class' => '']) }}>
    @if ($submit)
        <form wire:submit="{{ $submit }}">
    @endif
    <div class="overflow-hidden rounded-2xl border border-outline-variant/50 bg-canvas shadow-sm">
        @if (isset($title) || isset($icon))
            <div class="border-b border-outline-variant/50 bg-surface-dim/30 px-5 py-4">
                <div class="flex items-start gap-3 sm:items-center">
                    @if (isset($icon))
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-outline-variant/50 bg-surface-dim text-primary shadow-sm">
                            {{ $icon }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-ink">{{ $title ?? '' }}</h3>
                    </div>
                </div>
            </div>
        @endif

        <div class="px-5 py-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                {{ $form ?? $slot }}
            </div>
        </div>

        @if (isset($actions))
            <div class="flex flex-col-reverse gap-2 border-t border-outline-variant/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-end">
                {{ $actions }}
            </div>
        @endif
    </div>
    @if ($submit)
        </form>
    @endif
</div>
