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
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="ess-eyebrow">{{ __('Halaman') }}</p>
                <h1 id="{{ $titleId }}" class="mt-1 text-xl font-semibold tracking-tight text-ink sm:text-2xl">{{ $title }}</h1>
                @if ($desc)
                    <p id="{{ $descriptionId }}" class="mt-1 text-sm text-on-surface-variant">{{ $desc }}</p>
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
