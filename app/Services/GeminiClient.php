<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\HrKnowledgeBaseAgent;
use App\Exceptions\BusinessRuleException;
use Laravel\Ai\Embeddings;
use Throwable;

/**
 * GeminiClient — adapter for Google Gemini via Laravel AI SDK.
 *
 * Previously a raw HTTP wrapper; now delegates to laravel/ai SDK internally.
 * Kept for backward compatibility with KnowledgeBaseService and EmbeddingService.
 *
 * @see https://laravel.com/docs/ai-sdk
 */
class GeminiClient
{
    public function __construct(
        protected ?string $embeddingModel = null,
        protected ?string $llmModel = null,
    ) {
        $this->embeddingModel ??= (string) config('services.gemini.embedding_model', 'text-embedding-004');
        $this->llmModel ??= (string) config('services.gemini.model', 'gemini-2.5-flash');
    }

    /**
     * Generate embedding 768D dari text via Laravel AI SDK.
     *
     * @return array<int, float> 768 dimensi float
     */
    public function embed(string $text): array
    {
        if ($text === '' || mb_strlen($text) > 30_000) {
            throw new BusinessRuleException('Teks untuk embedding harus 1-30000 karakter.');
        }

        try {
            $embeddings = Embeddings::for([$text])
                ->dimensions(768)
                ->generate(model: $this->embeddingModel);

            $vector = $embeddings->first() ?? [];

            if (! is_array($vector) || count($vector) !== 768) {
                throw new BusinessRuleException('Embedding SDK tidak mengembalikan 768 dimensi.');
            }

            return array_map('floatval', $vector);
        } catch (Throwable $e) {
            throw new BusinessRuleException('Gagal generate embedding: '.$e->getMessage());
        }
    }

    /**
     * Generate RAG response via HrKnowledgeBaseAgent.
     *
     * @param  array<int, array{content: string, source: string}>  $context
     */
    public function generateContent(string $question, array $context = []): string
    {
        $contextText = $this->buildContextSection($context);

        $prompt = <<<PROMPT
KONTEKS:
{$contextText}

PERTANYAAN: {$question}

JAWABAN:
PROMPT;

        try {
            $agent = new HrKnowledgeBaseAgent;
            $response = $agent->prompt($prompt);

            return $response['answer'] ?? throw new BusinessRuleException('Agent tidak mengembalikan jawaban.');
        } catch (Throwable $e) {
            throw new BusinessRuleException('Gagal generate jawaban: '.$e->getMessage());
        }
    }

    /**
     * Health check — returns true if Gemini provider is configured.
     */
    public function isHealthy(): bool
    {
        try {
            $agent = new HrKnowledgeBaseAgent;
            $response = $agent->prompt('Test');

            return isset($response['answer']);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Format context chunks for prompt LLM.
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
}
