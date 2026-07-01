@props(['type' => 'table', 'rows' => 5, 'cols' => 4])

<div {{ $attributes->merge(['class' => 'animate-pulse']) }} role="status" aria-label="{{ __('Memuat') }}">
    @if ($type === 'table')
        <div class="space-y-3">
            <div class="flex gap-4">
                @foreach (range(1, $cols) as $c)
                    <div class="h-4 flex-1 rounded bg-surface-dim"></div>
                @endforeach
            </div>
            @foreach (range(1, $rows) as $r)
                <div class="flex gap-4">
                    @foreach (range(1, $cols) as $c)
                        <div class="h-3 flex-1 rounded bg-surface-dim/50"></div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @elseif ($type === 'card')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (range(1, $rows) as $r)
                <div class="space-y-3 rounded-xl border border-outline-variant/50 p-4">
                    <div class="h-4 w-3/4 rounded bg-surface-dim"></div>
                    <div class="h-3 w-1/2 rounded bg-surface-dim/50"></div>
                    <div class="h-3 w-full rounded bg-surface-dim/30"></div>
                </div>
            @endforeach
        </div>
    @elseif ($type === 'form')
        <div class="space-y-4">
            @foreach (range(1, $rows) as $r)
                <div class="space-y-1">
                    <div class="h-3 w-1/4 rounded bg-surface-dim"></div>
                    <div class="h-10 w-full rounded-xl bg-surface-dim/50"></div>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex items-center justify-center py-8">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-outline-variant border-t-ink"></div>
        </div>
    @endif
    <span class="sr-only">{{ __('Loading...') }}</span>
</div>
