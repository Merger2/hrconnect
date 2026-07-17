<div
    x-data="knowledgeBaseChat()"
    class="mx-auto flex max-w-4xl flex-col overflow-hidden rounded-2xl border border-white/10 bg-black/60 shadow-[0_20px_40px_rgba(0,0,0,0.4)]"
    style="height: min(70vh, 640px);"
>
    {{-- header --}}
    <div class="flex items-center justify-between gap-3 border-b border-white/10 bg-black/40 px-5 py-3.5">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/5 ring-1 ring-white/10">
                <span class="material-symbols-outlined text-white/80 text-xl">smart_toy</span>
            </div>
            <div>
                <h3 class="font-display text-sm font-semibold text-white/90 tracking-wide">HRConnect AI</h3>
                <p class="text-[11px] text-white/40 font-light">Asisten HR — Powered by RAG</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="relative flex h-1.5 w-1.5">
                <span x-show="isStreaming" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success/60"></span>
                <span :class="isStreaming ? 'bg-success' : 'bg-white/20'" class="relative inline-flex h-1.5 w-1.5 rounded-full"></span>
            </span>
            <span x-text="isStreaming ? 'Menulis...' : 'Online'" class="text-[11px] font-medium" :class="isStreaming ? 'text-success' : 'text-white/40'"></span>
        </div>
    </div>

    {{-- messages --}}
    <div x-ref="messages" class="flex-1 space-y-4 overflow-y-auto px-5 py-5 scroll-smooth">
        {{-- welcome --}}
        <div x-show="messages.length === 0" class="flex h-full flex-col items-center justify-center text-center">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-white/5 border border-white/10">
                <span class="material-symbols-outlined text-white/60 text-3xl">forum</span>
            </div>
            <h4 class="font-display text-base font-semibold text-white/90 mb-1.5">Ada yang bisa dibantu?</h4>
            <p class="max-w-xs text-[11px] text-white/40 leading-relaxed">Tanya seputar kebijakan HR, cuti, BPJS, payroll, atau aturan perusahaan.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-2">
                <button @click="input = 'Bagaimana kebijakan cuti tahunan?'; sendMessage()" class="rounded-lg border border-white/10 bg-white/5 px-3.5 py-2 text-[11px] font-medium text-white/80 hover:bg-white/10 transition-colors">
                    Kebijakan cuti tahunan?
                </button>
                <button @click="input = 'Bagaimana cara daftar BPJS Kesehatan?'; sendMessage()" class="rounded-lg border border-white/10 bg-white/5 px-3.5 py-2 text-[11px] font-medium text-white/80 hover:bg-white/10 transition-colors">
                    Cara daftar BPJS?
                </button>
                <button @click="input = 'Jam kerja dan kebijakan lembur?'; sendMessage()" class="rounded-lg border border-white/10 bg-white/5 px-3.5 py-2 text-[11px] font-medium text-white/80 hover:bg-white/10 transition-colors">
                    Jam kerja & lembur?
                </button>
            </div>
        </div>

        {{-- message list --}}
        <template x-for="(msg, index) in messages" :key="index">
            <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'" class="animate-fade-in">
                <div x-show="msg.role === 'assistant'" class="mt-1 mr-2.5 flex-shrink-0">
                    <div class="flex h-6 w-6 items-center justify-center rounded-md bg-white/5 border border-white/10">
                        <span class="material-symbols-outlined text-white/60 text-sm">smart_toy</span>
                    </div>
                </div>
                <div
                    :class="msg.role === 'user'
                        ? 'bg-white/10 text-white rounded-xl rounded-br-sm'
                        : 'bg-white/5 border border-white/10 rounded-xl rounded-bl-sm'"
                    class="max-w-[80%] px-4 py-2.5 text-sm leading-relaxed"
                >
                    <p x-html="formatMessage(msg.content)" class="whitespace-pre-wrap break-words" :class="msg.role === 'user' ? 'text-white' : 'text-white/90'"></p>
                </div>
            </div>
        </template>

        {{-- typing indicator --}}
        <div x-show="isStreaming && !currentStream" x-transition class="flex justify-start">
            <div class="mr-2.5 mt-1 flex-shrink-0">
                <div class="flex h-6 w-6 items-center justify-center rounded-md bg-white/5 border border-white/10">
                    <span class="material-symbols-outlined text-white/60 text-sm">smart_toy</span>
                </div>
            </div>
            <div class="rounded-lg rounded-bl-sm border border-white/10 bg-white/5 px-4 py-3">
                <div class="flex items-center gap-1.5">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white/30"></span>
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white/30" style="animation-delay: 0.2s"></span>
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white/30" style="animation-delay: 0.4s"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- input --}}
    <div class="border-t border-white/10 bg-black/40 px-4 py-3">
        <form @submit.prevent="sendMessage" class="flex items-end gap-2.5">
            <div class="flex-1">
                <textarea
                    x-model="input"
                    x-ref="input"
                    :disabled="isStreaming"
                    @keydown.enter.prevent="if (!$event.shiftKey) sendMessage()"
                    rows="1"
                    x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 120) + 'px'"
                    placeholder="Tanya tentang kebijakan HR, cuti, BPJS, payroll..."
                    class="w-full resize-none rounded-xl border border-blue-500/50 bg-white/5 px-3.5 py-2.5 text-sm text-white placeholder-white/30 outline-none focus:border-blue-500/50 focus:ring-1 focus:ring-blue-500/20 transition-colors"
                ></textarea>
            </div>
            <button
                type="submit"
                :disabled="isStreaming || !input.trim()"
                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary text-on-primary transition-opacity disabled:opacity-40 hover:bg-primary-deep hover:shadow-modal"
            >
                <span class="material-symbols-outlined text-sm">arrow_upward</span>
            </button>
        </form>
    </div>
</div>