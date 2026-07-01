export default function () {
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
                        ...window.apiHeaders(),
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                        'X-CSRF-TOKEN': token,
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
                this.messages.push({ role: 'assistant', content: '' });
                const agentIndex = this.messages.length - 1;

                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.content;
                    const syncResp = await fetch('/api/v1/knowledgebase/chat', {
                        method: 'POST',
                        headers: {
                            ...window.apiHeaders(),
                            'Content-Type': 'application/json',
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
