<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\KnowledgeBaseCategory;
use App\Enums\KnowledgeBaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Jobs\ProcessKnowledgeBaseEmbedding;
use App\Models\KnowledgeBase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * KnowledgeBaseService — orchestrator RAG (Retrieval-Augmented Generation).
 *
 * Flow:
 * 1. uploadPdf() → simpan file + extract teks + chunk + bulk insert chunks
 *    sebagai KB records dengan status=PROCESSING.
 * 2. Dispatch ProcessKnowledgeBaseEmbedding Job per chunk untuk async embed.
 * 3. chat($question) → embed question → vector search top-5 → generate via Gemini
 *    → fallback ke pg_trgm kalau Gemini down.
 *
 * Polymorphic morph: knowledgeable_type/_id menunjuk ke parent doc atau model HR.
 * Untuk PDF upload, parent default = first chunk's KB record (self-reference).
 *
 * Sumber: PRD §13, AGENTS.md "3 Core Thesis Features", api-contracts.md §12.
 */
class KnowledgeBaseService
{
    public function __construct(
        protected GeminiClient $gemini,
        protected EmbeddingService $embedding,
    ) {}

    /**
     * Chat AI RAG flow.
     *
     * 1. Embed question via Gemini
     * 2. Vector search top-5 chunks
     * 3. Generate response via Gemini 2.5 Flash dengan context
     * 4. Fallback ke pg_trgm kalau Gemini fail
     *
     * @return array{answer: string, sources: array<int, array{id: int, title: string, snippet: string}>, fallback: bool, model: string}
     */
    public function chat(string $question): array
    {
        $question = trim($question);

        if (mb_strlen($question) < 5 || mb_strlen($question) > 500) {
            throw new BusinessRuleException('Pertanyaan harus 5-500 karakter.');
        }

        try {
            // Tier 1: vector search via Gemini embedding
            $queryEmbedding = $this->gemini->embed($question);
            $chunks = $this->embedding->searchSimilar($queryEmbedding, topK: 5);

            $context = $chunks->map(fn (KnowledgeBase $kb) => [
                'content' => $kb->content,
                'source' => $kb->title.($kb->page_number !== null ? " (chunk #{$kb->page_number})" : ''),
            ])->all();

            $answer = $this->gemini->generateContent($question, $context);

            return [
                'answer' => $answer,
                'sources' => $chunks->map(fn (KnowledgeBase $kb) => [
                    'id' => $kb->id,
                    'title' => $kb->title,
                    'snippet' => mb_substr($kb->content, 0, 200),
                    'page_number' => $kb->page_number,
                    'source_document' => $kb->source_document,
                ])->all(),
                'fallback' => false,
                'model' => config('services.gemini.model'),
            ];
        } catch (Throwable $e) {
            Log::warning('Gemini RAG flow gagal, fallback ke pg_trgm', [
                'error' => $e->getMessage(),
                'question' => $question,
            ]);

            return $this->fallbackKeywordSearch($question);
        }
    }

    /**
     * Fallback kalau Gemini API down — pg_trgm keyword search.
     *
     * @return array{answer: string, sources: array<int, array{id: int, title: string, snippet: string}>, fallback: bool, model: string}
     */
    protected function fallbackKeywordSearch(string $question): array
    {
        $chunks = $this->embedding->searchByKeyword($question, topK: 5);

        if ($chunks->isEmpty()) {
            return [
                'answer' => 'Maaf, tidak ada informasi yang cocok dengan pertanyaan Anda di basis data HRConnect saat ini. Sistem AI sedang offline.',
                'sources' => [],
                'fallback' => true,
                'model' => 'pg_trgm',
            ];
        }

        $snippets = $chunks->map(fn (KnowledgeBase $kb) => "- {$kb->title}: ".mb_substr($kb->content, 0, 150))
            ->implode("\n");

        return [
            'answer' => "Sistem AI sedang offline. Berikut hasil pencarian keyword yang mungkin relevan:\n\n{$snippets}",
            'sources' => $chunks->map(fn (KnowledgeBase $kb) => [
                'id' => $kb->id,
                'title' => $kb->title,
                'snippet' => mb_substr($kb->content, 0, 200),
                'page_number' => $kb->page_number,
                'source_document' => $kb->source_document,
            ])->all(),
            'fallback' => true,
            'model' => 'pg_trgm',
        ];
    }

    /**
     * Upload PDF + chunk + dispatch embedding jobs.
     *
     * Setiap chunk jadi 1 row knowledge_bases (page_number = chunk index).
     * Polymorphic morph default: self-reference (first chunk = parent).
     */
    public function uploadPdf(
        UploadedFile $pdf,
        string $title,
        ?KnowledgeBaseCategory $category = null,
        ?Model $owner = null,
    ): KnowledgeBase {
        if ($pdf->getClientOriginalExtension() !== 'pdf' || $pdf->getMimeType() !== 'application/pdf') {
            throw new BusinessRuleException('File harus PDF.');
        }

        if ($pdf->getSize() > 10 * 1024 * 1024) {
            throw new BusinessRuleException('PDF maksimal 10 MB.');
        }

        $filename = uniqid('kb_').'.pdf';
        $path = $pdf->storeAs('knowledgebase', $filename, 'local');
        $absolutePath = storage_path('app/'.$path);

        $rawText = $this->embedding->extractTextFromPdf($absolutePath);
        $chunks = $this->embedding->chunkText($rawText);

        if (empty($chunks)) {
            throw new BusinessRuleException('PDF tidak menghasilkan chunk yang valid.');
        }

        $createdRecords = DB::transaction(function () use ($chunks, $title, $category, $filename, $owner) {
            $morphType = $owner ? $owner::class : KnowledgeBase::class;
            $morphIdPlaceholder = $owner?->getKey() ?? 0;
            $records = [];

            foreach ($chunks as $index => $chunkContent) {
                $kb = KnowledgeBase::create([
                    'knowledgeable_type' => $morphType,
                    'knowledgeable_id' => $morphIdPlaceholder ?: 1,
                    'title' => $title,
                    'content' => $chunkContent,
                    'category' => $category ?? KnowledgeBaseCategory::GENERAL,
                    'status' => KnowledgeBaseStatus::PROCESSING,
                    'source_document' => $filename,
                    'page_number' => $index,
                ]);

                $records[] = $kb;
            }

            return $records;
        });

        // B-2: Dispatch jobs AFTER transaction commit
        foreach ($createdRecords as $kb) {
            ProcessKnowledgeBaseEmbedding::dispatch($kb);
        }

        // First chunk dianggap sebagai "head record" untuk reference
        return $createdRecords[0];
    }

    /**
     * Re-trigger embedding untuk satu KB record (manual reindex).
     */
    public function reindex(KnowledgeBase $kb): void
    {
        $kb->update(['status' => KnowledgeBaseStatus::PROCESSING]);
        ProcessKnowledgeBaseEmbedding::dispatch($kb);
    }

    /**
     * Hapus KB record beserta semua chunks dengan source_document yang sama.
     *
     * B-15: Wrapping in DB::transaction to prevent partial delete.
     */
    public function deleteKnowledgeBase(KnowledgeBase $kb): int
    {
        return DB::transaction(function () use ($kb) {
            if ($kb->source_document) {
                return KnowledgeBase::where('source_document', $kb->source_document)->delete();
            }

            $kb->delete();

            return 1;
        });
    }
}
