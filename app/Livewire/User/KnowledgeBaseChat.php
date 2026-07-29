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

        $this->messages[] = [
            'role' => 'assistant',
            'text' => __('Hello! I can help answer questions about company policies, HR procedures, and more. What would you like to know?'),
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

        // Add user message
        $this->messages[] = [
            'role' => 'user',
            'text' => $question,
        ];

        // Add placeholder for assistant response (streaming)
        $responseIndex = count($this->messages);
        $this->messages[] = [
            'role' => 'assistant',
            'text' => '',
            'sources' => [],
            'is_streaming' => true,
        ];

        $this->isLoading = true;

        try {
            $answer = '';
            $finalSources = [];
            $newConversationId = null;
            $isFallback = false;

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
            }

            $this->conversationId = $newConversationId;

            // Update the placeholder with final response after streaming completes
            $this->messages[$responseIndex] = [
                'role' => 'assistant',
                'text' => $answer,
                'sources' => $finalSources,
                'fallback' => $isFallback,
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
                'text' => __('Hello! I can help answer questions about company policies, HR procedures, and more. What would you like to know?'),
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
