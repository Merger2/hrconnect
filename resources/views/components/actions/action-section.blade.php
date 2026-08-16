@props(['title', 'description', 'content'])

<div {{ $attributes->merge(['class' => '']) }}>
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        @if ($title)
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                @if (isset($icon))
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        {{ $icon }}
                    </div>
                @endif
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                    @if ($description)
                        <p class="sr-only">{{ $description }}</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="px-5 py-4">
            {{ $content }}
        </div>

        @if (isset($actions))
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-5 py-3">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
