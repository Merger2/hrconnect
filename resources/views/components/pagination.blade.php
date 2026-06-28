@props(['current' => 1, 'total' => 1, 'perPage' => 15, 'simple' => false])

@php
$lastPage = max(1, (int) ceil($total / max(1, $perPage)));
$current = min(max(1, $current), $lastPage);

$range = 2;
$start = max(1, $current - $range);
$end = min($lastPage, $current + $range);

if ($start > 2) {
    $pages = [1, '...'];
} elseif ($start === 2) {
    $pages = [1];
} else {
    $pages = [];
}

for ($i = $start; $i <= $end; $i++) {
    $pages[] = $i;
}

if ($end < $lastPage - 1) {
    $pages[] = '...';
    $pages[] = $lastPage;
} elseif ($end === $lastPage - 1) {
    $pages[] = $lastPage;
}
@endphp

@if ($lastPage <= 1)
    <div></div>
@elseif ($simple)
    <div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-4']) }}>
        <button
            @click="page = Math.max(1, page - 1)"
            x-bind:disabled="page <= 1"
            class="flex items-center gap-1 rounded-xl border border-outline-variant bg-canvas px-4 py-2 text-sm font-medium text-ink transition-colors hover:bg-surface-dim disabled:opacity-40">
            <span class="material-symbols-outlined text-lg">chevron_left</span>
            {{ __('Previous') }}
        </button>
        <span class="text-sm text-on-surface-variant">
            <span x-text="page"></span> / {{ $lastPage }}
        </span>
        <button
            @click="page = Math.min({{ $lastPage }}, page + 1)"
            x-bind:disabled="page >= {{ $lastPage }}"
            class="flex items-center gap-1 rounded-xl border border-outline-variant bg-canvas px-4 py-2 text-sm font-medium text-ink transition-colors hover:bg-surface-dim disabled:opacity-40">
            {{ __('Next') }}
            <span class="material-symbols-outlined text-lg">chevron_right</span>
        </button>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center gap-1']) }}>
        <button
            @click="page = Math.max(1, page - 1)"
            x-bind:disabled="page <= 1"
            class="flex h-9 w-9 items-center justify-center rounded-lg text-sm text-on-surface-variant transition-colors hover:bg-surface-dim disabled:opacity-30">
            <span class="material-symbols-outlined text-lg">chevron_left</span>
        </button>

        @foreach ($pages as $p)
            @if ($p === '...')
                <span class="flex h-9 w-9 items-center justify-center text-sm text-on-surface-variant">...</span>
            @else
                <button
                    @click="page = {{ $p }}"
                    @class([
                        'flex h-9 w-9 items-center justify-center rounded-lg text-sm font-medium transition-colors',
                        'bg-ink text-white' => $p === $current,
                        'text-ink hover:bg-surface-dim' => $p !== $current,
                    ])>
                    {{ $p }}
                </button>
            @endif
        @endforeach

        <button
            @click="page = Math.min({{ $lastPage }}, page + 1)"
            x-bind:disabled="page >= {{ $lastPage }}"
            class="flex h-9 w-9 items-center justify-center rounded-lg text-sm text-on-surface-variant transition-colors hover:bg-surface-dim disabled:opacity-30">
            <span class="material-symbols-outlined text-lg">chevron_right</span>
        </button>
    </div>
@endif
