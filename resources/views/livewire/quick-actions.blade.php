<div x-data="{ showMore: false }" class="space-y-4">
    <div>
        <h3 class="text-sm font-semibold text-white/90 mb-4">
            {{ __('Akses Cepat') }}
        </h3>

        <!-- Primary Actions -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($primaryItems as $item)
                <a href="{{ $item['href'] }}" wire:navigate
                   class="group flex flex-col items-center gap-2 rounded-xl border border-white/10 bg-black/30 p-4 text-center transition-all duration-200 hover:border-white/20 hover:bg-black/40 hover:-translate-y-0.5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/5 {{ $item['tone'] }}">
                        <span class="material-symbols-outlined text-lg text-white/90">{{ $item['icon'] }}</span>
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-medium text-white/90">{{ $item['label'] }}</p>
                        <p class="mt-0.5 text-xs leading-tight text-white/50">{{ $item['description'] }}</p>
                    </div>
                </a>
            @endforeach

            <button type="button" @click="showMore = !showMore"
                    class="group flex flex-col items-center gap-2 rounded-xl border border-dashed border-white/10 bg-black/30 p-4 text-center transition-all hover:border-white/20 hover:bg-black/40">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/5 text-white/70">
                    <span class="material-symbols-outlined text-lg">more_horiz</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-white/90">{{ __('Lainnya') }}</p>
                    <p class="mt-0.5 text-xs leading-tight text-white/50">{{ __('Buka menu lengkap') }}</p>
                </div>
            </button>
        </div>
    </div>

    {{-- More Drawer --}}
    <div x-cloak x-show="showMore"
         x-on:keydown.escape.window="showMore = false"
         class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 backdrop-blur-sm sm:items-center"
         @click.self="showMore = false">
        <div x-show="showMore"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="w-full max-w-lg rounded-t-2xl border-t border-white/10 bg-black/80 p-6 shadow-[0_-20px_40px_rgba(0,0,0,0.4)] sm:rounded-2xl">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h4 class="text-lg font-semibold text-white/90">{{ __('Menu Lengkap') }}</h4>
                    <p class="text-sm text-white/50">{{ __('Akses fitur lainnya di sini') }}</p>
                </div>
                <button @click="showMore = false" class="rounded-lg p-1 text-white/50 hover:bg-white/5 transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="max-h-[60vh] space-y-4 overflow-y-auto">
                @foreach ($moreGroups as $groupName => $items)
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-white/40">{{ $groupName }}</p>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach ($items as $item)
                                <a href="{{ $item['href'] }}" wire:navigate
                                   class="group flex items-center gap-2 rounded-lg p-2 text-sm text-white/80 transition-colors hover:bg-white/5">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-md bg-white/5 {{ $item['tone'] }}">
                                        <span class="material-symbols-outlined text-base text-white/90">{{ $item['icon'] }}</span>
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