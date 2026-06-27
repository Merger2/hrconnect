<div
    x-data="knowledgeBaseChat()"
    class="mx-auto flex max-w-3xl flex-col overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-low shadow-sm"
    style="height: min(70vh, 640px);"
>
    {{-- header --}}
    <div class="flex items-center gap-3 border-b border-outline-variant bg-surface-container px-5 py-3.5">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-ink/10">
            <span class="material-symbols-outlined text-ink/70 text-xl">smart_toy</span>
        </div>
        <div>
            <h3 class="font-display text-sm font-semibold text-ink tracking-wide">HRConnect AI</h3>
            <p class="text-xs text-on-surface-variant/70 font-light">Asisten HR — Powered by Gemini</p>
        </div>
        <div class="ml-auto flex items-center gap-1.5">
            <span class="relative flex h-1.5 w-1.5">
                <span x-show="isStreaming" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success opacity-60"></span>
                <span :class="isStreaming ? 'bg-success' : 'bg-muted-soft'" class="relative inline-flex h-1.5 w-1.5 rounded-full"></span>
            </span>
            <span x-text="isStreaming ? 'Menulis...' : 'Online'" class="text-xs font-medium" :class="isStreaming ? 'text-success' : 'text-muted-soft'"></span>
        </div>
    </div>

    {{-- messages --}}
    <div x-ref="messages" class="flex-1 space-y-4 overflow-y-auto px-5 py-5 scroll-smooth">
        {{-- welcome --}}
        <div x-show="messages.length === 0" class="flex h-full flex-col items-center justify-center text-center">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-ink/5 border border-outline-variant">
                <span class="material-symbols-outlined text-ink/60 text-3xl">forum</span>
            </div>
            <h4 class="font-display text-base font-semibold text-ink mb-1.5">Ada yang bisa dibantu?</h4>
            <p class="max-w-xs text-xs text-muted-soft leading-relaxed">Tanya seputar kebijakan HR, cuti, BPJS, payroll, atau aturan perusahaan.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-2">
                <button @click="input = 'Bagaimana kebijakan cuti tahunan?'; sendMessage()" class="rounded-lg border border-outline-variant bg-canvas px-3.5 py-2 text-xs font-medium text-body hover:bg-surface-container-highest transition-colors">
                    Kebijakan cuti tahunan?
                </button>
                <button @click="input = 'Bagaimana cara daftar BPJS Kesehatan?'; sendMessage()" class="rounded-lg border border-outline-variant bg-canvas px-3.5 py-2 text-xs font-medium text-body hover:bg-surface-container-highest transition-colors">
                    Cara daftar BPJS?
                </button>
                <button @click="input = 'Jam kerja dan kebijakan lembur?'; sendMessage()" class="rounded-lg border border-outline-variant bg-canvas px-3.5 py-2 text-xs font-medium text-body hover:bg-surface-container-highest transition-colors">
                    Jam kerja & lembur?
                </button>
            </div>
        </div>

        {{-- message list --}}
        <template x-for="(msg, index) in messages" :key="index">
            <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'" class="animate-fade-in">
                <div x-show="msg.role === 'assistant'" class="mt-1 mr-2.5 flex-shrink-0">
                    <div class="flex h-6 w-6 items-center justify-center rounded-md bg-ink/10 border border-outline-variant">
                        <span class="material-symbols-outlined text-ink/60 text-sm">smart_toy</span>
                    </div>
                </div>
                <div
                    :class="msg.role === 'user'
                        ? 'bg-ink text-canvas rounded-2xl rounded-br-sm'
                        : 'bg-surface-container border border-outline-variant rounded-2xl rounded-bl-sm'"
                    class="max-w-[80%] px-4 py-2.5 text-sm leading-relaxed"
                >
                    <p x-html="formatMessage(msg.content)" class="whitespace-pre-wrap break-words" :class="msg.role === 'user' ? 'text-canvas' : 'text-body'"></p>
                </div>
            </div>
        </template>

        {{-- typing indicator --}}
        <div x-show="isStreaming && !currentStream" x-transition class="flex justify-start">
            <div class="mr-2.5 mt-1 flex-shrink-0">
                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-ink/10 border border-outline-variant">
                    <span class="material-symbols-outlined text-ink/60 text-sm">smart_toy</span>
                </div>
            </div>
            <div class="rounded-2xl rounded-bl-sm border border-outline-variant bg-surface-container px-4 py-3">
                <div class="flex items-center gap-1.5">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-muted-soft"></span>
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-muted-soft" style="animation-delay: 0.2s"></span>
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-muted-soft" style="animation-delay: 0.4s"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- input --}}
    <div class="border-t border-outline-variant bg-surface-container px-4 py-3">
        <form @submit.prevent="sendMessage" class="flex items-end gap-2.5">
            <div class="flex-1">
                <textarea
                    x-model="input"
                    x-ref="input"
                    :disabled="isStreaming"
                    @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                    rows="1"
                    x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px'"
                    placeholder="Tanya tentang kebijakan HR..."
                    class="w-full resize-none rounded-xl border border-outline-variant bg-canvas px-3.5 py-2.5 text-sm text-body placeholder-muted-soft outline-none focus:border-ink focus:ring-1 focus:ring-ink/20 transition-colors"
                ></textarea>
            </div>
            <button
                type="submit"
                :disabled="isStreaming || !input.trim()"
                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-ink text-canvas transition-opacity disabled:opacity-40 hover:opacity-90"
            >
                <span class="material-symbols-outlined text-sm">arrow_upward</span>
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function knowledgeBaseChat() {
    return {
        messages: [],
        input: '',
        conversationId: null,
        isStreaming: false,
        currentStream: '',

        async sendMessage() {
            const message = this.input.trim();
            if (!message || this.isStreaming) return;

            this.messages.push({ role: 'user', content: message });
            this.input = '';
            this.isStreaming = true;
            this.currentStream = '';

            this.$refs.input.style.height = 'auto';
            this.scrollToBottom();

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;

                const response = await fetch('/api/v1/knowledgebase/chat-stream', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                        'X-CSRF-TOKEN': token,
                        'Authorization': 'Bearer ' + (window.Laravel?.sanctumToken || ''),
                    },
                    body: JSON.stringify({
                        question: message,
                        conversation_id: this.conversationId,
                    }),
                });

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                this.messages.push({ role: 'assistant', content: '' });
                const agentIndex = this.messages.length - 1;

                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    const chunk = decoder.decode(value);
                    const lines = chunk.split('\n');

                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            const payload = line.slice(6);

                            if (payload === '[DONE]') continue;

                            try {
                                const data = JSON.parse(payload);

                                if (data.text) {
                                    this.currentStream += data.text;
                                    this.messages[agentIndex].content = this.currentStream;
                                    this.scrollToBottom();
                                }

                                if (data.conversation_id) {
                                    this.conversationId = data.conversation_id;
                                }

                                if (data.sources) {
                                    this.messages[agentIndex].sources = data.sources;
                                }
                            } catch (e) {
                                // skip malformed
                            }
                        }
                    }
                }
            } catch (error) {
                // fallback: try sync endpoint
                this.messages.push({ role: 'assistant', content: '' });
                const agentIndex = this.messages.length - 1;

                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.content;
                    const syncResp = await fetch('/api/v1/knowledgebase/chat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer ' + (window.Laravel?.sanctumToken || ''),
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ question: message }),
                    });

                    if (syncResp.ok) {
                        const json = await syncResp.json();
                        this.messages[agentIndex].content = json.data?.answer || 'Maaf, tidak ada jawaban yang tersedia.';
                    } else {
                        this.messages[agentIndex].content = 'Maaf, layanan sedang tidak tersedia. Silakan coba lagi.';
                    }
                } catch {
                    this.messages[agentIndex].content = 'Maaf, layanan sedang tidak tersedia. Silakan coba lagi.';
                }

                this.scrollToBottom();
            } finally {
                this.isStreaming = false;
                this.currentStream = '';
            }
        },

        formatMessage(content) {
            if (!content) return '';
            return content
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/^- (.*)/gm, '&bull; $1')
                .replace(/\n/g, '<br>');
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messages;
                container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
            });
        },
    };
}
</script>
@endpush
