<div wire:poll.1s="pollProgress" x-data="{ show: $wire.progressId > 0 }" x-show="show" x-cloak>
    <div class="rounded-xl border border-outline-variant bg-surface-container-low p-4">
        <div class="mb-2 flex items-center justify-between text-sm">
            <span class="font-medium text-ink" x-text="$wire.label"></span>
            <span class="text-on-surface-variant" x-text="$wire.percentage + '%'"></span>
        </div>
        <div class="h-2.5 w-full overflow-hidden rounded-full bg-surface-dim">
            <div class="h-full rounded-full bg-ink transition-all duration-500"
                 :style="'width: ' + $wire.percentage + '%'"></div>
        </div>
        <div x-show="$wire.status === 'processing'" class="mt-2 flex items-center gap-2 text-xs text-on-surface-variant">
            <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-info"></span>
            {{ __('Processing...') }}
        </div>
    </div>
</div>
