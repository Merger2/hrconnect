@props(['applyLabel' => __('Apply'), 'resetLabel' => __('Reset')])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    @if (isset($filters))
        {{ $filters }}
    @else
        {{ $slot }}
    @endif

    @if (!($noButtons ?? false))
        <button
            @click="filterApply?.()"
            class="rounded-xl bg-ink px-4 py-2 text-xs font-semibold text-white transition-colors hover:opacity-90">
            {{ $applyLabel }}
        </button>
        <button
            @click="filterReset?.()"
            class="rounded-xl border border-outline-variant bg-canvas px-4 py-2 text-xs font-semibold text-ink transition-colors hover:bg-surface-dim">
            {{ $resetLabel }}
        </button>
    @endif
</div>
