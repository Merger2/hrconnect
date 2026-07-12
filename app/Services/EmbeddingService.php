<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\KnowledgeBaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\KnowledgeBase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Embeddings;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * EmbeddingService — orchestrate PDF extraction + chunking + vector storage.
 *
 * Flow upload PDF:
 * 1. extractTextFromPdf($filePath) → raw string
 * 2. chunkText($text) → array of chunks (60 tokens, overlap 10 per PRD §13.1)
 * 3. Per chunk → Embeddings::for() → store sebagai row di knowledge_bases
 *    dengan page_number=chunk_index, embedding=768D vector
 *
 * Vector search pakai pgvector cosine distance: `embedding <=> '[...]'::vector`.
 * Top-K default 5 (sesuai PRD §13.1).
 */
class EmbeddingService
{
    /**
     * Approximate 1 token ≈ 4 char untuk Bahasa Indonesia.
     * 60 tokens ≈ 240 char per chunk, 10 token overlap ≈ 40 char.
     */
    private const CHUNK_CHARS = 240;

    private const OVERLAP_CHARS = 40;

    /**
     * Extract teks dari PDF file path.
     *
     * @throws BusinessRuleException kalau file tidak ada / parse error
     */
    public function extractTextFromPdf(string $filePath): string
    {
        if (! is_readable($filePath)) {
            throw new BusinessRuleException("PDF tidak dapat dibaca: {$filePath}");
        }

        try {
            $parser = new PdfParser;
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
        } catch (\Throwable $e) {
            Log::error('PDF parsing gagal', ['file' => $filePath, 'error' => $e->getMessage()]);

            throw new BusinessRuleException('PDF tidak dapat di-parse: '.$e->getMessage());
        }

        $text = trim($text);

        if ($text === '') {
            throw new BusinessRuleException('PDF kosong atau scanned image (perlu OCR — defer ke V2).');
        }

        return $text;
    }

    /**
     * Split text jadi chunks dengan overlap.
     *
     * @return array<int, string>
     */
    public function chunkText(string $text, int $chunkChars = self::CHUNK_CHARS, int $overlapChars = self::OVERLAP_CHARS): array
    {
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text ?? '');

        if ($text === '') {
            return [];
        }

        $chunks = [];
        $textLength = mb_strlen($text);
        $position = 0;
        $stride = max(1, $chunkChars - $overlapChars);

        while ($position < $textLength) {
            $chunk = mb_substr($text, $position, $chunkChars);

            // Skip chunk yang terlalu pendek (kurang berguna untuk RAG)
            if (mb_strlen(trim($chunk)) >= 20) {
                $chunks[] = trim($chunk);
            }

            $position += $stride;
        }

        return $chunks;
    }

    /**
     * Generate embedding 768D dari text via Laravel AI SDK.
     *
     * @return array<int, float> 768 dimensi float
     *
     * @throws BusinessRuleException
     */
    public function embed(string $text): array
    {
        if ($text === '' || mb_strlen($text) > 30_000) {
            throw new BusinessRuleException('Teks untuk embedding harus 1-30000 karakter.');
        }

        try {
            $embeddingModel = (string) config('services.gemini.embedding_model', 'text-embedding-004');

            $embeddings = Embeddings::for([$text])
                ->dimensions(768)
                ->generate(model: $embeddingModel);

            $vector = $embeddings->first() ?? [];

            if (! is_array($vector) || count($vector) !== 768) {
                throw new BusinessRuleException('Embedding SDK tidak mengembalikan 768 dimensi.');
            }

            return array_map('floatval', $vector);
        } catch (\Throwable $e) {
            throw new BusinessRuleException('Gagal generate embedding: '.$e->getMessage());
        }
    }

    /**
     * Process single KnowledgeBase: re-embed content yang sudah ada di kolom content.
     *
     * Dipakai oleh ProcessKnowledgeBaseEmbedding Job untuk reindex.
     */
    public function processKnowledgeBase(KnowledgeBase $kb): void
    {
        if (empty($kb->content)) {
            $kb->update(['status' => KnowledgeBaseStatus::ERROR]);

            return;
        }

        try {
            $embedding = $this->embed($kb->content);
            $vectorString = $this->formatVector($embedding);

            if (DB::getDriverName() === 'pgsql') {
                DB::statement(
                    'UPDATE knowledge_bases SET embedding = ?::vector, status = ?, updated_at = ? WHERE id = ?',
                    [$vectorString, KnowledgeBaseStatus::READY->value, now(), $kb->id]
                );
            } else {
                $kb->update([
                    'embedding' => $vectorString,
                    'status' => KnowledgeBaseStatus::READY,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Process embedding gagal', ['kb_id' => $kb->id, 'error' => $e->getMessage()]);
            $kb->update(['status' => KnowledgeBaseStatus::ERROR]);
            throw $e;
        }
    }

    /**
     * Vector similarity search via pgvector cosine distance.
     * Return top-K KnowledgeBase records yang paling relevan.
     *
     * Fallback ke pg_trgm full-text search kalau pgvector tidak tersedia.
     *
     * @param  array<int, float>  $queryEmbedding  768D vector dari EmbeddingService::embed
     * @return Collection<int, KnowledgeBase>
     */
    public function searchSimilar(array $queryEmbedding, int $topK = 5): Collection
    {
        if (DB::getDriverName() !== 'pgsql') {
            // SQLite test env: return ready records (no real vector search)
            return KnowledgeBase::query()
                ->where('status', KnowledgeBaseStatus::READY)
                ->limit($topK)
                ->get();
        }

        $vectorString = $this->formatVector($queryEmbedding);

        return KnowledgeBase::query()
            ->where('status', KnowledgeBaseStatus::READY)
            ->whereNotNull('embedding')
            ->select('*')
            ->selectRaw('embedding <=> ?::vector AS distance', [$vectorString])
            ->orderBy('distance')
            ->limit($topK)
            ->get();
    }

    /**
     * pg_trgm fallback search untuk saat Gemini API down.
     *
     * @return Collection<int, KnowledgeBase>
     */
    public function searchByKeyword(string $query, int $topK = 5): Collection
    {
        if (DB::getDriverName() !== 'pgsql') {
            return KnowledgeBase::query()
                ->where('status', KnowledgeBaseStatus::READY)
                ->where('content', 'like', '%'.addslashes($query).'%')
                ->limit($topK)
                ->get();
        }

        return KnowledgeBase::query()
            ->where('status', KnowledgeBaseStatus::READY)
            ->selectRaw('*, similarity(content, ?) AS sim_score', [$query])
            ->whereRaw('content % ?', [$query])
            ->orderByDesc('sim_score')
            ->limit($topK)
            ->get();
    }

    /**
     * Format float array → pgvector format string '[0.1,0.2,...]'.
     *
     * B-24: Guard empty array — return empty vector string instead of invalid SQL.
     *
     * @param  array<int, float>  $vector
     */
    public function formatVector(array $vector): string
    {
        if (empty($vector)) {
            return '[]';
        }

        return '['.implode(',', array_map('floatval', $vector)).']';
    }
}
