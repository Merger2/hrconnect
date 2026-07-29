@props([
    'title',
    'description' => null,
    'containerClass' => 'w-full px-4 sm:px-6 lg:px-8 2xl:px-10',
    'showDescription' => false,
])

@php
    $titleId = \Illuminate\Support\Str::slug($title).'-title';
    $descriptionId = $description ? \Illuminate\Support\Str::slug($title).'-description' : null;
@endphp

<section class="relative" aria-labelledby="{{ $titleId }}" @if($descriptionId) aria-describedby="{{ $descriptionId }}" @endif>
    <div {{ $attributes->merge(['class' => $containerClass . ' py-3 sm:py-4 lg:py-5']) }}>
        @if($title || isset($actions))
            <div class="mb-3 flex flex-col gap-2.5 border-b border-slate-200/70 pb-3 dark:border-slate-800 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0">
                    @if($title)
                        <h1 id="{{ $titleId }}" class="truncate text-lg font-semibold tracking-tight text-slate-950 dark:text-white sm:text-xl">
                            {{ $title }}
                        </h1>
                    @endif

                    @if ($description)
                        <p id="{{ $descriptionId }}" class="{{ $showDescription ? 'mt-1 max-w-3xl text-sm leading-5 text-slate-600 dark:text-slate-300' : 'sr-only' }}">
                            {{ $description }}
                        </p>
                    @endif
                </div>

                @isset($actions)
                    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                        {{ $actions }}
                    </div>
                @endisset
            </div>
        @endif

        @isset($toolbar)
            <div class="mb-3 rounded-xl border border-slate-200/70 bg-white p-2.5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                {{ $toolbar }}
            </div>
        @endisset

        <div class="space-y-3">
            {{ $slot }}
        </div>
    </div>
</section>
