<div class="user-page-shell" x-data="kbChat()" x-init="init()">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="kb-chat-title" class="user-page-surface relative flex flex-col" style="min-height: calc(100vh - 10rem);">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Knowledge Base Chat')"
                title-id="kb-chat-title"
                module="kb"
                class="border-b-0 shrink-0">
                <x-slot name="icon">
                    <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" />
                </x-slot>
                <x-slot name="actions">
                    <button wire:click="startNewChat"
                        class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                        <x-heroicon-o-plus class="h-5 w-5" />
                        <span>{{ __('New Chat') }}</span>
                    </button>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0 flex flex-col flex-1">
                {{-- Messages Area --}}
                <div class="flex-1 overflow-y-auto px-4 sm:px-5 py-4 space-y-4" x-ref="messagesContainer">
                    @forelse($messages as $index => $msg)
                        <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}"
                            wire:key="msg-{{ $index }}">
                            <div class="max-w-[85%] sm:max-w-[75%] {{ $msg['role'] === 'user' ? 'order-1' : 'order-1' }}">
                                {{-- Message Bubble --}}
                                <div class="rounded-2xl px-4 py-3 text-sm leading-relaxed shadow-sm
                                    {{ $msg['role'] === 'user'
                                        ? 'bg-primary-600 text-white rounded-br-md'
                                        : 'bg-gray-50 text-gray-900 border border-gray-100 rounded-bl-md' }}">
                                    @if(($msg['is_streaming'] ?? false))
                                        {{-- Streaming in progress — wire:stream always present in DOM --}}
                                        <div class="whitespace-pre-wrap" wire:stream="kb-response"></div>
                                    @else
                                        <div class="whitespace-pre-wrap">{{ $msg['text'] }}</div>

                                        @if(($msg['fallback'] ?? false) && !($msg['is_welcome'] ?? false))
                                            <div class="mt-2 flex items-center gap-1.5 text-xs text-amber-600">
                                                <x-heroicon-o-exclamation-triangle class="h-3.5 w-3.5" />
                                                <span>{{ __('Powered by keyword search (AI unavailable)') }}</span>
                                            </div>
                                            @if($msg['no_results'] ?? false)
                                                <p class="mt-1 text-xs text-amber-600">{{ __('Tidak ada hasil relevan di basis pengetahuan untuk pertanyaan ini.') }}</p>
                                            @endif
                                        @endif
                                    @endif
                                </div>

                                {{-- Sources --}}
                                @if(!empty($msg['sources']) && !($msg['is_welcome'] ?? false))
                                    <div class="mt-1.5 px-1">
                                        <button type="button"
                                            @click="toggleSources({{ $index }})"
                                            class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-700 transition"
                                            :aria-expanded="expandedSources.has({{ $index }}).toString()">
                                            <x-heroicon-o-chevron-right class="h-3.5 w-3.5 transition-transform duration-200"
                                                x-bind:class="{ 'rotate-90': expandedSources.has({{ $index }}) }" />
                                            <span>{{ __('Sources') }}</span>
                                            <span class="text-gray-400">({{ count($msg['sources']) }})</span>
                                        </button>
                                        <div x-show="expandedSources.has({{ $index }})"
                                            x-collapse
                                            style="display: none;"
                                            class="mt-1 space-y-1">
                                            @foreach($msg['sources'] as $source)
                                                <div class="rounded-lg border border-gray-100 bg-white px-3 py-2 text-xs text-gray-600">
                                                    <span class="font-medium text-gray-800">{{ $source['title'] }}</span>
                                                    @if(!empty($source['snippet']))
                                                        <p class="mt-0.5 text-gray-500 line-clamp-2">{{ $source['snippet'] }}</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center text-center py-16">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-50 to-sky-50 text-emerald-500 ring-1 ring-inset ring-emerald-200">
                                <x-heroicon-o-chat-bubble-left-right class="h-8 w-8" />
                            </div>
                            <h3 class="mt-4 text-lg font-bold text-gray-900">{{ __('Ask me anything') }}</h3>
                            <p class="mt-1 text-sm text-gray-500 max-w-sm">
                                {{ __('I can help with company policies, HR procedures, benefits, and more.') }}
                            </p>
                        </div>
                    @endforelse
                </div>

                {{-- Input Area --}}
                <div class="shrink-0 border-t border-gray-100 bg-white/80 backdrop-blur-sm px-4 sm:px-5 py-3">
                    {{-- Real-time loading indicator — request lifecycle, bukan nunggu render --}}
                    <div wire:loading wire:target="sendMessage, processAnswer"
                         class="mb-2 flex items-center gap-2 text-sm text-gray-500"
                         role="status" aria-live="polite">
                        <span class="flex gap-1">
                            <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay: 0ms"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay: 150ms"></span>
                            <span class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay: 300ms"></span>
                        </span>
                        <span>{{ __('Thinking...') }}</span>
                    </div>
                    <form @submit.prevent="submitMessage()" class="flex items-end gap-2">
                        <div class="flex-1 relative">
                            <textarea
                                wire:model="question"
                                x-ref="questionInput"
                                @keydown.enter.prevent="if(!$event.shiftKey) submitMessage()"
                                rows="1"
                                style="field-sizing: content"
                                maxlength="500"
                                placeholder="{{ __('Type your question...') }}"
                                class="block w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 resize-none transition focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                :disabled="{{ $isLoading ? 'true' : 'false' }}"
                            ></textarea>
                            <span class="absolute bottom-2 right-3 text-xs text-gray-400" x-text="charCount()"></span>
                        </div>
                        <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="sendMessage, processAnswer"
                            :disabled="!canSend() || $wire.isLoading"
                            class="wcag-touch-target inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-sm transition hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            aria-label="{{ __('Send') }}">
                            <template x-if="!$wire.isLoading">
                                <x-heroicon-o-paper-airplane class="h-5 w-5" />
                            </template>
                            <template x-if="$wire.isLoading">
                                <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                        </button>
                    </form>
                    <p class="mt-1.5 text-xs text-gray-400 px-1">
                        {{ __('Ask questions about company policies, leave, payroll, and more.') }}
                    </p>
                </div>
            </div>
        </section>
    </div>

    @push('scripts')
    <script>
        function kbChat() {
            return {
                expandedSources: new Set(),

                init() {
                    this.$nextTick(() => this.scrollToBottom());

                    // Watch Livewire messages property — scoped to this component, no global leak
                    this.$wire.$watch('messages', () => {
                        this.$nextTick(() => this.scrollToBottom());
                    });
                },

                scrollToBottom() {
                    const container = this.$refs.messagesContainer;
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                },

                toggleSources(index) {
                    if (this.expandedSources.has(index)) {
                        this.expandedSources.delete(index);
                    } else {
                        this.expandedSources.add(index);
                    }
                },

                canSend() {
                    return this.$wire?.question?.trim()?.length >= 5;
                },

                charCount() {
                    const input = this.$refs.questionInput;
                    return input ? `${input.value.length}/500` : '0/500';
                },

                async submitMessage() {
                    if (!this.canSend() || this.$wire.isLoading) return;

                    try {
                        // Phase 1: fast render — user message + "Thinking..." placeholder
                        await this.$wire.sendMessage();
                        this.$nextTick(() => this.scrollToBottom());

                        // Phase 2: AI call + streaming (placeholder already in DOM)
                        await this.$wire.processAnswer();
                    } catch (e) {
                        // Validation error or server exception — Livewire renders the error
                    } finally {
                        this.$nextTick(() => this.scrollToBottom());
                    }
                },
            };
        }
    </script>
    @endpush
</div>
