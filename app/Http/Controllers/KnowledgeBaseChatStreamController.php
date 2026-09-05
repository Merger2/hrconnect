<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\KnowledgeBase\KnowledgeBaseService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SSE endpoint untuk KB chat — pola ship-ai-with-laravel (course resmi
 * Laravel AI): streaming AI lewat endpoint text/event-stream + fetch/reader
 * di client, BUKAN $this->stream() Livewire (bug vendor Livewire 4.2.x +
 * Symfony http-foundation 8 membuat commit morph hilang setelah streaming,
 * lihat AUDIT.md #60).
 *
 * Frames yang dikirim (semua `data: <json>\n\n`):
 *  - {"text": "..."}            → delta teks jawaban (efek ketik per kata)
 *  - {"conversation_id": "...", "sources": [...], "fallback": bool,
 *     "no_results": bool}       → meta akhir jawaban
 *  - [DONE]                     → penutup stream
 *  - {"error": "..."}           → kegagalan (tidak pernah diam / no silent degradation)
 */
class KnowledgeBaseChatStreamController extends Controller
{
    public function __invoke(Request $request, KnowledgeBaseService $kbService, AuthFactory $auth): StreamedResponse
    {
        if (! Gate::allows('view_knowledgebase')) {
            abort(403);
        }

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'conversation_id' => ['nullable', 'string', 'max:64'],
        ]);

        $question = trim($validated['question']);

        if (mb_strlen($question) < 5 && ! is_greeting_question($question)) {
            throw ValidationException::withMessages([
                'question' => ['Pertanyaan harus 5-500 karakter.'],
            ]);
        }

        return response()->stream(function () use ($kbService, $auth, $question, $validated) {
            $meta = [
                'conversation_id' => null,
                'sources' => [],
                'fallback' => false,
                'no_results' => false,
            ];

            try {
                foreach ($kbService->chatStream(
                    question: $question,
                    conversationId: ($validated['conversation_id'] ?? null) ?: null,
                    user: $auth->guard()->user(),
                ) as $yield) {
                    if (isset($yield['text']) && $yield['text'] !== '') {
                        $this->sendFrame(['text' => $yield['text']]);
                    }

                    foreach (['conversation_id', 'sources', 'fallback', 'no_results'] as $key) {
                        if (array_key_exists($key, $yield)) {
                            $meta[$key] = $yield[$key];
                        }
                    }
                }

                if ($meta['conversation_id'] !== null || $meta['sources'] !== [] || $meta['fallback'] || $meta['no_results']) {
                    $this->sendFrame($meta);
                }

                $this->sendRaw("data: [DONE]\n\n");
            } catch (\Throwable $e) {
                logger()->error('KB chat SSE gagal', [
                    'error' => $e->getMessage(),
                    'question' => $question,
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->sendFrame(['error' => 'Terjadi kendala saat memproses pertanyaan. Silakan coba lagi.']);
                $this->sendRaw("data: [DONE]\n\n");
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    protected function sendFrame(array $payload): void
    {
        echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";
        @ob_flush();
        flush();
    }

    protected function sendRaw(string $frame): void
    {
        echo $frame;
        @ob_flush();
        flush();
    }
}
