@props(['submit'])

<div {{ $attributes->merge(['class' => '']) }}>
    <form wire:submit="{{ $submit }}">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-start gap-3 border-b border-slate-100 px-5 py-4 sm:items-center">
                @if (isset($icon))
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        {{ $icon }}
                    </div>
                @endif
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                    @if (isset($description) && filled(trim(strip_tags((string) $description))))
                        <div class="sr-only">{{ $description }}</div>
                    @endif
                </div>
            </div>

            <div class="px-5 py-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                    {{ $form }}
                </div>
            </div>

            @if (isset($actions))
                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 px-5 py-3 sm:flex-row sm:items-center sm:justify-end">
                    {{ $actions }}
                </div>
            @endif
        </div>
    </form>
</div>
