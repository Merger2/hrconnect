<div x-data="{ showMore: false }" class="space-y-4">
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-ink">{{ __('Akses Cepat') }}</h3>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($primaryItems as $item)
                <a href="{{ $item['href'] }}" wire:navigate
                   class="group flex flex-col items-center gap-2 rounded-xl border border-outline-variant/60 bg-canvas p-4 text-center shadow-sm transition hover:border-outline-variant hover:shadow-md">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $item['tone'] }}">
                        <span class="material-symbols-outlined text-lg">{{ $item['icon'] }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-ink">{{ $item['label'] }}</p>
                        <p class="mt-0.5 text-xs leading-tight text-on-surface-variant">{{ $item['description'] }}</p>
                    </div>
                </a>
            @endforeach

            <button type="button" @click="showMore = !showMore"
                    class="group flex flex-col items-center gap-2 rounded-xl border border-dashed border-outline-variant/60 bg-canvas p-4 text-center transition hover:border-outline-variant hover:shadow-sm">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-info/10 text-info">
                    <span class="material-symbols-outlined text-lg">more_horiz</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-ink">{{ __('Lainnya') }}</p>
                    <p class="mt-0.5 text-xs leading-tight text-on-surface-variant">{{ __('Buka menu lengkap') }}</p>
                </div>
            </button>
        </div>
    </div>

    {{-- More Drawer --}}
    <div x-cloak x-show="showMore"
         x-on:keydown.escape.window="showMore = false"
         class="fixed inset-0 z-50 flex items-end justify-center bg-ink/40 sm:items-center"
         @click.self="showMore = false">
        <div x-show="showMore"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="w-full max-w-lg rounded-t-lg bg-canvas p-6 shadow-xl sm:rounded-lg">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h4 class="text-lg font-semibold text-ink">{{ __('Menu Lengkap') }}</h4>
                    <p class="text-sm text-on-surface-variant">{{ __('Akses fitur lainnya di sini') }}</p>
                </div>
                <button @click="showMore = false" class="rounded-lg p-1 text-on-surface-variant hover:bg-surface-dim">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="max-h-[60vh] space-y-4 overflow-y-auto">
                @foreach ($moreGroups as $groupName => $items)
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ $groupName }}</p>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach ($items as $item)
                                <a href="{{ $item['href'] }}" wire:navigate
                                   class="flex items-center gap-2 rounded-lg p-2 text-sm text-ink transition hover:bg-surface-dim">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-md {{ $item['tone'] }}">
                                        <span class="material-symbols-outlined text-base">{{ $item['icon'] }}</span>
                                    </span>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
