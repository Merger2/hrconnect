@props(['search' => null, 'searchPlaceholder' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="flex flex-1 items-center gap-2">
        @if ($search ?? false)
            <div class="relative flex-1 sm:max-w-xs">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-lg text-on-surface-variant">search</span>
                <input
                    type="search"
                    placeholder="{{ $searchPlaceholder ?? __('Search...') }}"
                    x-model="search"
                    class="h-10 w-full rounded-xl border border-outline-variant bg-canvas pl-10 pr-4 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink"
                />
            </div>
        @endif
        @if (isset($filters))
            <div class="flex items-center gap-2">{{ $filters }}</div>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
    @endif
</div>
