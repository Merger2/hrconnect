<x-app-layout>
<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="kb-detail-title" class="user-page-surface">
            <x-user.page-header
                :back-href="route('knowledge-base.index')"
                :title="$knowledgeBase->title"
                title-id="kb-detail-title"
                module="kb"
                class="border-b-0">
                <x-slot name="icon">
                    <x-heroicon-o-document-text class="h-5 w-5" />
                </x-slot>
                <x-slot name="actions">
                    <a href="{{ route('knowledge-base.chat', ['q' => $knowledgeBase->title]) }}"
                       class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700">
                        <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" />
                        <span>{{ __('Ask AI about this') }}</span>
                    </a>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                {{-- Meta --}}
                <div class="mb-4 flex flex-wrap items-center gap-2 px-4 sm:px-5">
                    @if ($knowledgeBase->category)
                        <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-0.5 text-[11px] font-medium text-sky-700 ring-1 ring-inset ring-sky-600/20">
                            {{ ucfirst($knowledgeBase->category->value) }}
                        </span>
                    @endif
                    @if ($knowledgeBase->source_document)
                        <span class="inline-flex items-center gap-1 text-[11px] text-slate-500">
                            <x-heroicon-o-document-arrow-down class="h-3.5 w-3.5" />
                            {{ $knowledgeBase->source_document }}
                        </span>
                    @endif
                    @if ($knowledgeBase->created_at)
                        <span class="text-[11px] text-slate-500">
                            {{ $knowledgeBase->created_at->translatedFormat('d M Y') }}
                        </span>
                    @endif
                    @if ($knowledgeBase->chunk_count)
                        <span class="text-[11px] text-slate-500">{{ $knowledgeBase->chunk_count }} {{ __('chunks') }}</span>
                    @endif
                </div>

                {{-- Content --}}
                <div class="px-4 pb-6 sm:px-5">
                    <article class="prose-sm max-w-none rounded-2xl border border-gray-100 bg-white p-5 text-sm leading-relaxed text-gray-700 shadow-sm">
                        <div class="whitespace-pre-line">{{ $knowledgeBase->content }}</div>
                    </article>

                    <div class="mt-6 flex flex-col items-center gap-3 text-center">
                        <p class="text-sm text-slate-500">{{ __('Butuh jawaban lebih spesifik? Tanyakan langsung ke asisten AI.') }}</p>
                        <a href="{{ route('knowledge-base.chat', ['q' => $knowledgeBase->title]) }}"
                           class="inline-flex items-center gap-2 rounded-2xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                            <x-heroicon-o-chat-bubble-left-right class="h-4 w-4" />
                            <span>{{ __('Ask the AI Assistant') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
</x-app-layout>
