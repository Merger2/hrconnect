<div
    class="user-page-shell"
    x-data="kbChat()"
    x-init="init()"
    data-kb-initial="{{ $initialQuestion }}"
    data-kb-welcome="{{ $welcomeMessage }}"
>
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="kb-chat-title" class="user-page-surface kb-chat-surface relative flex flex-col">
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
                    <button type="button"
                        @click="startNewChat()"
                        :disabled="isStreaming"
                        class="wcag-touch-target inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:opacity-50">
                        <x-heroicon-o-plus class="h-5 w-5" />
                        <span>{{ __('New Chat') }}</span>
                    </button>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0 flex flex-col flex-1 user-accent-card user-accent-card--kb">
                {{-- Messages Area --}}
                <div
                    x-ref="messagesContainer"
                    class="flex-1 overflow-y-auto px-4 sm:px-5 py-4 space-y-4"
                    role="log"
                    aria-live="polite"
                    aria-relevant="additions"
                >
                    <template x-for="(msg, index) in messages" :key="msg.id">
                        <div class="flex" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[85%] sm:max-w-[75%]">
                                {{-- Message Bubble --}}
                                <div
                                    class="rounded-2xl px-4 py-3 text-sm leading-relaxed shadow-sm"
                                    :class="msg.role === 'user'
                                        ? 'bg-primary-600 text-white rounded-br-md'
                                        : 'bg-gray-50 text-gray-900 border border-gray-100 rounded-bl-md'"
                                >
                                    <div class="whitespace-pre-wrap" x-text="msg.text"></div>

                                    {{-- Thinking indicator: menunggu delta pertama --}}
                                    <div x-show="msg.isStreaming && !msg.text" class="flex items-center gap-1.5 py-1" role="status">
                                        <span class="h-2 w-2 rounded-full bg-gray-400 animate-bounce"></span>
                                        <span class="h-2 w-2 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 150ms"></span>
                                        <span class="h-2 w-2 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 300ms"></span>
                                    </div>

                                    <template x-if="msg.error">
                                        <div class="mt-2 flex items-start gap-1.5 text-xs text-red-600">
                                            <x-heroicon-o-exclamation-triangle class="h-3.5 w-3.5 shrink-0 mt-0.5" />
                                            <span x-text="msg.text"></span>
                                        </div>
                                    </template>

                                    <template x-if="msg.fallback && !msg.is_welcome">
                                        <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                                            <x-heroicon-o-information-circle class="h-3.5 w-3.5" />
                                            <span>{{ __('Dijawab dari basis pengetahuan perusahaan') }}</span>
                                        </div>
                                    </template>
                                    <template x-if="msg.no_results">
                                        <p class="mt-1 text-xs text-slate-500">{{ __('Tidak ada hasil relevan di basis pengetahuan untuk pertanyaan ini.') }}</p>
                                    </template>
                                </div>

                                {{-- Sources --}}
                                <template x-if="msg.sources.length > 0 && !msg.isStreaming">
                                    <div class="mt-1.5 px-1">
                                        <button
                                            type="button"
                                            @click="toggleSources(index)"
                                            class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-700 transition"
                                            :aria-expanded="expandedSources.has(index).toString()"
                                        >
                                            <x-heroicon-o-chevron-right class="h-3.5 w-3.5 transition-transform duration-200"
                                                x-bind:class="{ 'rotate-90': expandedSources.has(index) }" />
                                            <span>{{ __('Sources') }}</span>
                                            <span class="text-slate-500" x-text="`(${msg.sources.length})`"></span>
                                        </button>
                                        <div x-show="expandedSources.has(index)" class="mt-1 space-y-1">
                                            <template x-for="(source, sIdx) in msg.sources" :key="sIdx">
                                                <div class="rounded-lg border border-gray-100 bg-white px-3 py-2 text-xs text-gray-600">
                                                    <span class="font-medium text-gray-800" x-text="source.title"></span>
                                                    <template x-if="source.snippet">
                                                        <p class="mt-0.5 text-gray-500 line-clamp-2" x-text="source.snippet"></p>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Suggestion chips — hanya saat percakapan baru (belum ada pesan user) --}}
                    <template x-if="messages.length === 1 && messages[0].is_welcome && !isStreaming">
                        <div class="px-1">
                            <p class="mb-2 text-xs font-medium text-slate-500">{{ __('Pertanyaan cepat') }}</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(suggestion, sIdx) in suggestions" :key="sIdx">
                                    <button
                                        type="button"
                                        @click="askSuggestion(suggestion)"
                                        class="wcag-touch-target rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700"
                                        x-text="suggestion"
                                    ></button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Input Area --}}
                <div class="shrink-0 border-t border-gray-100 bg-white px-4 sm:px-5 py-3">
                    <form @submit.prevent="submitMessage()" class="flex items-end gap-2">
                        <div class="flex-1 relative">
                            <textarea
                                x-model="input"
                                x-ref="questionInput"
                                aria-label="{{ __('Type your question...') }}"
                                @keydown.enter.prevent="if(!$event.shiftKey) submitMessage()"
                                rows="1"
                                style="field-sizing: content"
                                maxlength="500"
                                :disabled="isStreaming"
                                :placeholder="isStreaming ? '{{ __('Menunggu jawaban...') }}' : '{{ __('Type your question...') }}'"
                                class="block w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 resize-none transition focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:opacity-60"
                            ></textarea>
                            <span class="absolute bottom-2 right-3 text-xs text-slate-500" x-text="charCount()"></span>
                        </div>
                        <button
                            type="submit"
                            :disabled="!canSend() || isStreaming"
                            class="wcag-touch-target inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-md transition hover:bg-primary-700 hover:shadow-lg active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:bg-primary-600 disabled:hover:shadow-md"
                            aria-label="{{ __('Send') }}"
                        >
                            <template x-if="!isStreaming">
                                <x-heroicon-o-paper-airplane class="h-5 w-5" />
                            </template>
                            <template x-if="isStreaming">
                                <x-heroicon-o-arrow-path class="h-5 w-5 animate-spin" />
                            </template>
                        </button>
                    </form>
                    <p class="mt-1.5 text-xs text-slate-500 px-1">
                        {{ __('Ask questions about company policies, leave, payroll, and more.') }}
                    </p>
                </div>
            </div>
        </section>
    </div>

    @push('scripts')
    <script>
        let kbChatBooted = false; // Livewire morph saat load awal bisa
        // men-trigger Alpine init berulang kali — welcome hanya di-push sekali.

        function kbChat() {
            const makeWelcome = (text) => ({
                id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
                role: 'assistant',
                text: text,
                sources: [],
                is_welcome: true,
                isStreaming: false,
                fallback: false,
                no_results: false,
                error: false,
            });

            return {
                messages: [],
                suggestions: [
                    'Apa profil perusahaan?',
                    'Apa itu cuti tahunan?',
                    'Bagaimana cara absensi?',
                    'Kapan jadwal penggajian?',
                ],
                input: '',
                conversationId: null,
                isStreaming: false,
                expandedSources: new Set(),
                welcomeMessage: '',

                init() {
                    // Data awal dari atribut data-* (ter-escape Blade) dipilih
                    // karena helper JS-in-HTML dari Blade bisa pecah bila user
                    // ?q= mengandung apostrof.
                    const root = this.$root;
                    this.input = root.dataset.kbInitial || '';
                    this.welcomeMessage = root.dataset.kbWelcome || '';
                    if (!kbChatBooted) {
                        kbChatBooted = true;
                        this.messages.push(makeWelcome(this.welcomeMessage));
                    }
                    this.$nextTick(() => this.scrollToBottom());
                },

                uid() {
                    return `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
                },

                canSend() {
                    const q = (this.input || '').trim();
                    return q.length >= 5 || this.isGreetingQuestion(q);
                },

                isGreetingQuestion(q) {
                    const greetings = new Set(['halo', 'hai', 'hi', 'hello', 'hey', 'salam', 'assalamualaikum', 'assalamualikum', 'permisi', 'pagi', 'siang', 'sore', 'malam', 'selamat', 'apa', 'kabar', 'terima', 'kasih', 'makasih', 'thanks', 'thank', 'good', 'morning', 'afternoon', 'evening', 'min', 'kak', 'bang', 'bu', 'pak', 'mbak', 'mas', 'bro', 'ya', 'nih', 'dong', 'sih', 'deh', 'dll']);
                    const tokens = q.toLowerCase().replace(/[^\p{L}\p{N}\s]+/gu, ' ').trim().split(/\s+/).filter(Boolean);
                    return tokens.length > 0 && tokens.every(t => greetings.has(t));
                },

                async askSuggestion(question) {
                    if (this.isStreaming) return;
                    this.input = question;
                    await this.submitMessage();
                },

                async submitMessage() {
                    if (!this.canSend() || this.isStreaming) return;

                    const question = this.input.trim();
                    this.input = '';
                    this.isStreaming = true;

                    this.messages.push({ id: this.uid(), role: 'user', text: question, sources: [] });
                    this.messages.push({
                        id: this.uid(),
                        role: 'assistant',
                        text: '',
                        sources: [],
                        is_welcome: false,
                        isStreaming: true,
                        fallback: false,
                        no_results: false,
                        error: false,
                    });
                    // Index placeholder assistant = pesan TERAKHIR (setelah push).
                    const agentIndex = this.messages.length - 1;

                    this.scrollToBottom();

                    try {
                        const response = await fetch('/knowledge-base/chat/stream', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'text/event-stream',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                question: question,
                                conversation_id: this.conversationId,
                            }),
                        });

                        if (!response.ok || !response.body) {
                            throw new Error(`HTTP ${response.status}`);
                        }

                        const reader = response.body.getReader();
                        const decoder = new TextDecoder();

                        let buffer = '';

                        while (true) {
                            const { done, value } = await reader.read();
                            if (done) break;

                            buffer += decoder.decode(value, { stream: true });

                            // Pecah per baris; simpan sisa yang belum lengkap.
                            let newlineIndex;
                            while ((newlineIndex = buffer.indexOf('\n')) !== -1) {
                                const line = buffer.slice(0, newlineIndex).replace(/\r$/, '');
                                buffer = buffer.slice(newlineIndex + 1);

                                if (!line.startsWith('data: ')) continue;

                                const payload = line.slice(6).trim();
                                if (!payload) continue;

                                if (payload === '[DONE]') {
                                    this.messages[agentIndex].isStreaming = false;
                                    continue;
                                }

                                let data;
                                try {
                                    data = JSON.parse(payload);
                                } catch (e) {
                                    continue;
                                }

                                if (typeof data.text === 'string' && data.text !== '') {
                                    this.messages[agentIndex].text += data.text;
                                    this.scrollToBottom();
                                }

                                if (data.conversation_id) {
                                    this.conversationId = data.conversation_id;
                                }
                                if (Array.isArray(data.sources)) {
                                    this.messages[agentIndex].sources = data.sources;
                                }
                                if (typeof data.fallback === 'boolean') {
                                    this.messages[agentIndex].fallback = data.fallback;
                                }
                                if (typeof data.no_results === 'boolean') {
                                    this.messages[agentIndex].no_results = data.no_results;
                                }
                                if (data.error) {
                                    this.messages[agentIndex].text = data.error;
                                    this.messages[agentIndex].error = true;
                                    this.messages[agentIndex].sources = [];
                                }
                            }
                        }
                    } catch (error) {
                        console.error('KB chat stream error:', error);
                        this.messages[agentIndex].text = 'Maaf, terjadi kendala koneksi saat menjawab. Silakan coba lagi.';
                        this.messages[agentIndex].error = true;
                        this.messages[agentIndex].sources = [];
                    } finally {
                        this.messages[agentIndex].isStreaming = false;
                        this.isStreaming = false;
                        this.scrollToBottom();
                    }
                },

                startNewChat() {
                    if (this.isStreaming) return;

                    this.conversationId = null;
                    this.input = '';
                    this.expandedSources = new Set();
                    this.messages = [makeWelcome(this.welcomeMessage)];
                    this.$nextTick(() => this.scrollToBottom());
                },

                toggleSources(index) {
                    if (this.expandedSources.has(index)) {
                        this.expandedSources.delete(index);
                    } else {
                        this.expandedSources.add(index);
                    }
                },

                charCount() {
                    return `${(this.input || '').length}/500`;
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = this.$refs.messagesContainer;
                        if (container) {
                            container.scrollTop = container.scrollHeight;
                        }
                    });
                },
            };
        }
    </script>
    @endpush
</div>
