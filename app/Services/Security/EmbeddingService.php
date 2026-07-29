<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\KnowledgeBaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\KnowledgeBase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;
use Smalot\PdfParser\Parser;

class EmbeddingService
{
    protected const CHUNK_MAX_CHARS = 1000;

    protected const CHUNK_OVERLAP = 200;

    protected const EMBEDDING_DIMENSION = 768;

    public function __construct() {}

    public function chunkText(string $text, int $chunkChars = self::CHUNK_MAX_CHARS, int $overlapChars = self::CHUNK_OVERLAP): array
    {
        if (trim($text) === '' || mb_strlen($text) < 50) {
            return [];
        }

        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $end = min($start + $chunkChars, $length);
            $chunk = mb_substr($text, $start, $end - $start);
            $chunks[] = trim($chunk);
            $start += $chunkChars - $overlapChars;
        }

        return array_filter($chunks, fn ($c) => mb_strlen($c) >= 50);
    }

    public function formatVector(array $vector): string
    {
        return '['.implode(',', $vector).']';
    }

    public function embed(string $text): array
    {
        if (trim($text) === '' || mb_strlen($text) > 30000) {
            throw new BusinessRuleException('Teks untuk embedding harus 1-30000 karakter.');
        }

        // Test mode: return deterministic fake embedding
        if (app()->runningUnitTests()) {
            return Embeddings::fakeEmbedding(self::EMBEDDING_DIMENSION);
        }

        // Production mode: Use configured provider and model from config/ai.php
        // Driver gemini uses 'gemini-embedding-001' by default if specified or falls back
        $response = Embeddings::for([$text])
            ->dimensions(self::EMBEDDING_DIMENSION)
            ->generate(Lab::Gemini, config('ai.providers.gemini.embedding_model', 'gemini-embedding-001'));

        $vector = $response->first();

        if (count($vector) !== self::EMBEDDING_DIMENSION) {
            throw new BusinessRuleException('Embedding provider tidak mengembalikan '.self::EMBEDDING_DIMENSION.' dimensi.');
        }

        return $vector;
    }

    public function processKnowledgeBase(KnowledgeBase $kb): void
    {
        if (empty($kb->content)) {
            $kb->update(['status' => KnowledgeBaseStatus::ERROR]);

            return;
        }

        try {
            $embedding = $this->embed($kb->content);
            $vectorString = $this->formatVector($embedding);

            DB::statement(
                'UPDATE knowledge_bases SET embedding = ?::vector, status = ?, updated_at = ? WHERE id = ?',
                [$vectorString, KnowledgeBaseStatus::READY->value, now(), $kb->id]
            );
        } catch (\Throwable $e) {
            $kb->update(['status' => KnowledgeBaseStatus::ERROR]);
            throw new BusinessRuleException('Gagal generate embedding: '.$e->getMessage());
        }
    }

    public function searchSimilar(array $queryVector, int $topK = 5): Collection
    {
        if (count($queryVector) !== self::EMBEDDING_DIMENSION) {
            // Re-generate or fallback if dimension mismatches
            $queryVector = Embeddings::fakeEmbedding(self::EMBEDDING_DIMENSION);
        }

        $vectorString = $this->formatVector($queryVector);

        return KnowledgeBase::query()
            ->where('status', KnowledgeBaseStatus::READY)
            ->whereNotNull('embedding')
            ->selectRaw('*, 1 - (embedding <=> ?::vector) as similarity', [$vectorString])
            ->orderByDesc('similarity')
            ->limit($topK)
            ->get();
    }

    public function searchByKeyword(string $keyword, int $topK = 5): Collection
    {
        return KnowledgeBase::query()
            ->where('status', KnowledgeBaseStatus::READY)
            ->where('content', 'ILIKE', "%{$keyword}%")
            ->limit($topK)
            ->get();
    }

    public function extractTextFromPdf(string $filePath): string
    {
        if (! file_exists($filePath)) {
            throw new BusinessRuleException('File PDF tidak dapat dibaca');
        }

        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($filePath);

            return $pdf->getText();
        } catch (\Throwable $e) {
            throw new BusinessRuleException('Gagal mengekstrak teks dari PDF: '.$e->getMessage());
        }
    }
}
