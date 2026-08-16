<?php

namespace App\Livewire\User;

use App\Services\KnowledgeBase\KnowledgeBaseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class KnowledgeBaseChat extends Component
{
    use AuthorizesRequests;

    public string $question = '';

    public array $messages = [];

    public bool $isLoading = false;

    public ?string $conversationId = null;

    protected KnowledgeBaseService $kbService;

    public function boot(KnowledgeBaseService $kbService): void
    {
        $this->kbService = $kbService;
    }

    public function mount(): void
    {
        $this->authorize('view_knowledgebase');

        // Prefill dari ?q= (link "Tanya AI" di index/detail dokumen) —
        // user tinggal menekan kirim.
        $this->question = (string) request()->query('q', '');

        $this->messages[] = [
            'role' => 'assistant',
            'text' => __('Halo! Saya asisten AI perusahaan. Tanyakan apa saja seputar kebijakan dan prosedur kepegawaian.'),
            'sources' => [],
            'is_welcome' => true,
        ];
    }

    public function sendMessage(): void
    {
        $this->authorize('view_knowledgebase');

        $this->validate([
            'question' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $question = trim($this->question);
        $this->question = '';

        $this->appendUserAndPlaceholder($question);

        // NOTE: deliberately NO AI call here — this action must return fast so the
        // browser renders the user message + "Thinking..." placeholder immediately.
        // The AI call happens in processAnswer() (called from the frontend after
        // this render completes), so the UI never looks frozen while Gemini thinks.
    }

    /**
     * Jalur suggestion chip: kirim pertanyaan + streaming jawaban dalam SATU
     * request Livewire. (Dua-fase sendMessage → processAnswer tidak bisa
     * dipakai dari Alpine @click — request processAnswer selalu ditelan
     * Livewire setelah sendMessage, terlihat lewat E2E 2026-08-16.)
     */
    public function ask(string $question): void
    {
        $this->authorize('view_knowledgebase');

        $this->question = $question;

        $this->validate([
            'question' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $question = trim($this->question);
        $this->question = '';

        $this->appendUserAndPlaceholder($question);
        $this->streamAnswer(count($this->messages) - 1, $question);
    }

    public function processAnswer(): void
    {
        $this->authorize('view_knowledgebase');

        $responseIndex = count($this->messages) - 1;

        if ($responseIndex < 0 || ($this->messages[$responseIndex]['role'] ?? null) !== 'assistant') {
            $this->isLoading = false;

            return;
        }

        $question = (string) ($this->messages[$responseIndex - 1]['text'] ?? '');

        $this->streamAnswer($responseIndex, $question);
    }

    /**
     * Tambah pesan user + placeholder streaming ke riwayat.
     */
    protected function appendUserAndPlaceholder(string $question): void
    {
        $this->messages[] = [
            'role' => 'user',
            'text' => $question,
        ];

        $this->messages[] = [
            'role' => 'assistant',
            'text' => '',
            'sources' => [],
            'is_streaming' => true,
        ];

        $this->isLoading = true;
    }

    /**
     * Jalankan chatStream dan stream chunk ke browser, lalu finalisasi pesan.
     */
    protected function streamAnswer(int $responseIndex, string $question): void
    {
        try {
            $answer = '';
            $finalSources = [];
            $newConversationId = null;
            $isFallback = false;
            $noResults = false;

            // Stream via chatStream() generator — pushes chunks to browser progressively
            foreach ($this->kbService->chatStream(
                question: $question,
                conversationId: $this->conversationId,
                user: Auth::user(),
            ) as $yield) {
                if (isset($yield['text']) && $yield['text'] !== '') {
                    // Chunk the text for smoother streaming (aim for ~20-30 char chunks)
                    $text = $yield['text'];
                    $chunkSize = 30;
                    $textLength = mb_strlen($text);
                    for ($i = 0; $i < $textLength; $i += $chunkSize) {
                        $chunk = mb_substr($text, $i, $chunkSize);
                        $answer .= $chunk;
                        $this->stream($chunk, false, 'kb-response');
                        // Small delay between chunks for progressive feel
                        if ($i + $chunkSize < $textLength) {
                            usleep(15000); // 15ms
                        }
                    }
                }

                if (isset($yield['conversation_id'])) {
                    $newConversationId = $yield['conversation_id'];
                }

                if (isset($yield['sources'])) {
                    $finalSources = $yield['sources'];
                }

                if (isset($yield['fallback'])) {
                    $isFallback = (bool) $yield['fallback'];
                }

                if (isset($yield['no_results'])) {
                    $noResults = (bool) $yield['no_results'];
                }
            }

            $this->conversationId = $newConversationId;

            // Update the placeholder with final response after streaming completes
            $this->messages[$responseIndex] = [
                'role' => 'assistant',
                'text' => $answer,
                'sources' => $finalSources,
                'fallback' => $isFallback,
                'no_results' => $noResults,
                'is_streaming' => false,
            ];
        } catch (\Throwable $e) {
            $this->messages[$responseIndex] = [
                'role' => 'assistant',
                'text' => __('Sorry, I encountered an error. Please try again later.'),
                'sources' => [],
                'error' => true,
                'is_streaming' => false,
            ];
        } finally {
            $this->isLoading = false;
        }
    }

    public function startNewChat(): void
    {
        $this->conversationId = null;
        $this->question = '';
        $this->messages = [
            [
                'role' => 'assistant',
                'text' => __('Halo! Saya asisten AI perusahaan. Tanyakan apa saja seputar kebijakan dan prosedur kepegawaian.'),
                'sources' => [],
                'is_welcome' => true,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.user.knowledge-base-chat');
    }
}
