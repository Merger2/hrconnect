<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * GeminiClient — low-level HTTP wrapper untuk Google Gemini API.
 *
 * Endpoint:
 * - POST /v1beta/models/{embedding_model}:embedContent → 768D embedding
 * - POST /v1beta/models/{llm_model}:generateContent    → RAG response
 *
 * Mock mode (RAG_MOCK_MODE=true): bypass API, return canned response
 * untuk demo offline / Pest test tanpa Gemini API key.
 *
 * Retry: 3x dengan exponential backoff [1s, 2s, 4s].
 *
 * Rujukan:
 * - https://ai.google.dev/api/embeddings#text-embedding
 * - https://ai.google.dev/api/generate-content
 * - PRD §13.1, AGENTS.md "3 Core Thesis Features"
 */
class GeminiClient
{
    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $embeddingModel = null,
        protected ?string $llmModel = null,
        protected ?string $baseUrl = null,
        protected ?int $timeout = null,
        protected ?bool $mockMode = null,
        protected ?int $maxRetries = null,
    ) {
        $this->apiKey ??= (string) config('services.gemini.api_key', '');
        $this->embeddingModel ??= (string) config('services.gemini.embedding_model', 'text-embedding-004');
        $this->llmModel ??= (string) config('services.gemini.model', 'gemini-2.5-flash');
        $this->baseUrl ??= (string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $this->timeout ??= (int) config('services.gemini.timeout', 30);
        $this->mockMode ??= (bool) config('services.gemini.mock_mode', false);
        $this->maxRetries ??= (int) config('services.gemini.max_retries', 3);
    }

    /**
     * Generate embedding 768D dari text.
     *
     * @return array<int, float> 768 dimensi float
     *
     * @throws BusinessRuleException kalau API gagal setelah max retry
     */
    public function embed(string $text): array
    {
        if ($this->mockMode) {
            return $this->mockEmbedding();
        }

        if ($text === '' || mb_strlen($text) > 30_000) {
            throw new BusinessRuleException('Teks untuk embedding harus 1-30000 karakter.');
        }

        $url = "{$this->baseUrl}/models/{$this->embeddingModel}:embedContent?key={$this->apiKey}";

        $payload = [
            'content' => [
                'parts' => [['text' => $text]],
            ],
        ];

        $response = $this->retryRequest(fn () => Http::timeout($this->timeout)
            ->acceptJson()
            ->post($url, $payload));

        $values = $response->json('embedding.values');

        if (! is_array($values) || count($values) !== 768) {
            throw new BusinessRuleException('Embedding API tidak mengembalikan 768 dimensi.');
        }

        return array_map('floatval', $values);
    }

    /**
     * Generate RAG response dari prompt + context chunks.
     *
     * @param  string  $question  Pertanyaan user
     * @param  array<int, array{content: string, source: string}>  $context  Top-K chunks dari vector search
     */
    public function generateContent(string $question, array $context = []): string
    {
        if ($this->mockMode) {
            return $this->mockResponse($question, $context);
        }

        $url = "{$this->baseUrl}/models/{$this->llmModel}:generateContent?key={$this->apiKey}";

        $contextText = $this->buildContextSection($context);
        $systemPrompt = <<<PROMPT
Anda adalah asisten AI HRConnect untuk karyawan PT 521 Teknologi Indonesia.
Jawab pertanyaan user dalam Bahasa Indonesia berdasarkan KONTEKS yang diberikan.
Kalau jawaban tidak ada di konteks, jawab "Maaf, informasi tersebut belum tersedia di basis data HRConnect."
Jangan mengarang atau menggunakan pengetahuan eksternal.

KONTEKS:
{$contextText}

PERTANYAAN: {$question}

JAWABAN:
PROMPT;

        $payload = [
            'contents' => [
                ['parts' => [['text' => $systemPrompt]]],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 1024,
            ],
        ];

        $response = $this->retryRequest(fn () => Http::timeout($this->timeout)
            ->acceptJson()
            ->post($url, $payload));

        $answer = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($answer) || trim($answer) === '') {
            throw new BusinessRuleException('LLM tidak mengembalikan respons yang valid.');
        }

        return trim($answer);
    }

    /**
     * Health check — return true kalau API reachable.
     */
    public function isHealthy(): bool
    {
        if ($this->mockMode) {
            return true;
        }

        try {
            $url = "{$this->baseUrl}/models?key={$this->apiKey}";
            $response = Http::timeout(5)->get($url);

            return $response->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Retry HTTP request dengan exponential backoff.
     *
     * @param  callable():Response  $request
     */
    protected function retryRequest(callable $request): Response
    {
        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                $response = $request();

                if ($response->successful()) {
                    return $response;
                }

                // 4xx error → tidak retry (client bug)
                if ($response->clientError()) {
                    Log::warning('Gemini API client error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    throw new BusinessRuleException(
                        "Gemini API error {$response->status()}: ".$response->json('error.message', 'Unknown error')
                    );
                }

                // 5xx → retry dengan backoff
                $lastError = "HTTP {$response->status()}: ".$response->body();
            } catch (ConnectionException $e) {
                $lastError = $e->getMessage();
            } catch (BusinessRuleException $e) {
                throw $e;
            }

            // Exponential backoff: 1s, 2s, 4s
            if ($attempt < $this->maxRetries) {
                sleep(2 ** ($attempt - 1));
            }
        }

        Log::error('Gemini API failed after max retries', ['error' => $lastError]);

        throw new BusinessRuleException(
            "Gemini API tidak dapat diakses setelah {$this->maxRetries} percobaan. Detail: {$lastError}"
        );
    }

    /**
     * Format context chunks untuk prompt LLM.
     *
     * @param  array<int, array{content: string, source: string}>  $context
     */
    protected function buildContextSection(array $context): string
    {
        if (empty($context)) {
            return '(tidak ada konteks yang relevan ditemukan)';
        }

        $parts = [];
        foreach ($context as $i => $chunk) {
            $no = $i + 1;
            $source = $chunk['source'] ?? 'unknown';
            $content = $chunk['content'] ?? '';
            $parts[] = "[Sumber {$no}: {$source}]\n{$content}";
        }

        return implode("\n\n", $parts);
    }

    /**
     * Mock embedding untuk testing/demo. Deterministic hash-based vector.
     *
     * @return array<int, float>
     */
    protected function mockEmbedding(): array
    {
        // Deterministic vector berdasarkan random fixed-seed untuk konsistensi
        $vector = [];
        for ($i = 0; $i < 768; $i++) {
            $vector[] = round(sin($i * 0.1) * 0.3, 6);
        }

        return $vector;
    }

    /**
     * Mock RAG response untuk demo offline.
     *
     * @param  array<int, array{content: string, source: string}>  $context
     */
    protected function mockResponse(string $question, array $context): string
    {
        if (empty($context)) {
            return 'Maaf, informasi tersebut belum tersedia di basis data HRConnect. (mock mode)';
        }

        $sourceCount = count($context);

        return "Berdasarkan {$sourceCount} sumber yang ditemukan, jawaban untuk pertanyaan \"{$question}\": ".
            mb_substr($context[0]['content'] ?? '', 0, 200).'... (mock mode)';
    }
}
