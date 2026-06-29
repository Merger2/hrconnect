@props([
    'title',
    'description' => null,
    'subtitle' => null,
    'containerClass' => null,
])

@php
$desc = $description ?? $subtitle;
$titleId = \Illuminate\Support\Str::slug($title) . '-title';
$descriptionId = $desc ? \Illuminate\Support\Str::slug($title) . '-description' : null;
@endphp

<section aria-labelledby="{{ $titleId }}" @if ($descriptionId) aria-describedby="{{ $descriptionId }}" @endif>
    <div {{ $attributes->merge(['class' => $containerClass ?? 'space-y-6']) }}>
        <div class="flex flex-col gap-4 border-b border-outline-variant/50 pb-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 space-y-1">
                <h1 id="{{ $titleId }}" class="truncate text-2xl font-semibold tracking-tight text-ink">{{ $title }}</h1>
                @if ($desc)
                    <p id="{{ $descriptionId }}" class="max-w-3xl text-sm text-on-surface-variant">{{ $desc }}</p>
                @endif
            </div>
            @if (isset($actions))
                <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
            @endif
        </div>

        @if (isset($toolbar))
            <div class="rounded-xl border border-outline-variant/50 bg-surface-dim/50 p-3">{{ $toolbar }}</div>
        @endif

        <div class="space-y-6">
            {{ $slot }}
        </div>
    </div>
</section>
