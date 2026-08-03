<x-app-layout>
<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="kb-index-title" class="user-page-surface">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Knowledge Base')"
                title-id="kb-index-title"
                class="border-b-0">
                <x-slot name="icon">
                    <x-heroicon-o-document-text class="h-5 w-5" />
                </x-slot>
                <x-slot name="actions">
                    <a href="{{ route('knowledge-base.chat') }}"
                       class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700">
                        <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" />
                        <span>{{ __('Chat with AI') }}</span>
                    </a>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                {{-- Search --}}
                <div class="mb-5 px-4 sm:px-5">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.472 9.766l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <input type="search"
                               x-data
                               x-init="
                                   $el.addEventListener('input', function() {
                                       clearTimeout(this._searchTimer);
                                       this._searchTimer = setTimeout(() => {
                                           const q = this.value.trim();
                                           document.querySelectorAll('[data-kb-card]').forEach(card => {
                                               const title = card.dataset.title?.toLowerCase() || '';
                                               const desc = card.dataset.description?.toLowerCase() || '';
                                               const match = !q || title.includes(q.toLowerCase()) || desc.includes(q.toLowerCase());
                                               card.classList.toggle('hidden', !match);
                                           });
                                       }, 200);
                                   });
                               "
                               placeholder="{{ __('Search documents...') }}"
                               class="block w-full rounded-2xl border border-gray-200 bg-gray-50 py-3 pl-11 pr-4 text-sm text-gray-900 placeholder-gray-400 transition focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                    </div>
                </div>

                {{-- Document Grid --}}

                @if ($documents->isEmpty())
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-50 text-gray-300 ring-1 ring-inset ring-gray-200">
                            <x-heroicon-o-document-text class="h-8 w-8" />
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-gray-900">{{ __('No Documents Yet') }}</h3>
                        <p class="mt-1 max-w-sm text-sm text-gray-500">{{ __('Knowledge base documents will appear here once they are uploaded and processed.') }}</p>
                        <a href="{{ route('knowledge-base.chat') }}"
                           class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700">
                            <x-heroicon-o-chat-bubble-left-right class="h-4 w-4" />
                            <span>{{ __('Ask the AI Assistant') }}</span>
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-3 px-4 sm:grid-cols-2 sm:px-5 lg:grid-cols-3">
                        @foreach ($documents as $doc)
                            <div data-kb-card
                                 data-title="{{ $doc->title }}"
                                 data-description=""
                                 class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-all hover:shadow-md hover:border-gray-200 hover:-translate-y-0.5">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-50 to-sky-50 text-emerald-600 ring-1 ring-inset ring-emerald-100">
                                        <x-heroicon-o-document-text class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="font-semibold text-gray-900 truncate group-hover:text-primary-600 transition-colors">
                                            {{ $doc->title }}
                                        </h3>
                                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                            @if ($doc->category)
                                                <span class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-medium text-sky-700 ring-1 ring-inset ring-sky-600/20">
                                                    {{ ucfirst($doc->category->value) }}
                                                </span>
                                            @endif
                                            <span class="text-[10px] text-gray-400">
                                                {{ $doc->created_at?->translatedFormat('d M Y') }}
                                            </span>
                                            @if ($doc->chunk_count)
                                                <span class="text-[10px] text-gray-400">
                                                    {{ $doc->chunk_count }} {{ __('chunks') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Hover action --}}
                                <div class="mt-3 flex justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-primary-600">
                                        {{ __('View details') }}
                                        <x-heroicon-o-chevron-right class="h-3 w-3" />
                                    </span>
                                </div>

                                {{-- Full click target --}}
                                <a href="{{ route('knowledge-base.chat') }}?q={{ urlencode($doc->title) }}"
                                   class="absolute inset-0"
                                   aria-label="{{ __('Ask about') }}: {{ $doc->title }}">
                                    <span class="sr-only">{{ __('Ask about') }}: {{ $doc->title }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 text-center">
                        <a href="{{ route('knowledge-base.chat') }}"
                           class="inline-flex items-center gap-2 rounded-2xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                            <x-heroicon-o-chat-bubble-left-right class="h-4 w-4" />
                            <span>{{ __('Ask AI Assistant') }}</span>
                        </a>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
</x-app-layout>
