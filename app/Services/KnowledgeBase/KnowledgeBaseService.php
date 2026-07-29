<?php

declare(strict_types=1);

namespace App\Services\KnowledgeBase;

use App\Ai\Agents\HrKnowledgeBaseAgent;
use App\Enums\KnowledgeBaseCategory;
use App\Enums\KnowledgeBaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Jobs\ProcessKnowledgeBaseEmbedding;
use App\Models\KnowledgeBase;
use App\Models\User;
use App\Services\Security\EmbeddingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
        protected EmbeddingService $embedding,
    ) {}

    /**
     * Chat AI RAG — SSE streaming version.
     *
     * Yields SSE-compatible arrays for StreamedResponse.
     * Flow: embed question → vector search top-5 → stream via agent.
     *
     * @param  User|null  $user  User to associate conversation with (for memory persistence)
     * @return \Generator<int, array{text?: string, conversation_id?: string, sources?: array}, void, void>
     */
    public function chatStream(string $question, ?string $conversationId = null, ?User $user = null): \Generator
    {
        $question = trim($question);

        try {
            $queryEmbedding = $this->embedding->embed($question);
            $chunks = $this->embedding->searchSimilar($queryEmbedding, topK: 5);

            $context = $chunks->map(fn (KnowledgeBase $kb) => [
                'content' => $kb->content,
                'source' => $kb->title.($kb->page_number !== null ? " (chunk #{$kb->page_number})" : ''),
            ])->all();

            $contextText = $this->buildContextSection($context);
            $agentPrompt = <<<PROMPT
KONTEKS:
{$contextText}

PERTANYAAN: {$question}

JAWABAN:
PROMPT;

            $agent = $this->prepareAgent($user, $conversationId);
            // Streaming structured output is not supported by Gemini,
            // so we use sync prompt() and yield the full answer as a single event.
            $result = $agent->prompt($agentPrompt);

            yield ['text' => $result['answer'] ?? ''];

            $newConversationId = $result->conversationId ?? $conversationId ?? (string) Str::uuid();

            $sources = $chunks->map(fn (KnowledgeBase $kb) => [
                'id' => $kb->id,
                'title' => $kb->title,
                'snippet' => mb_substr($kb->content, 0, 200),
            ])->all();

            yield [
                'conversation_id' => $newConversationId,
                'sources' => $sources,
            ];
        } catch (Throwable $e) {
            Log::warning('Gemini RAG streaming gagal, fallback ke pg_trgm', [
                'error' => $e->getMessage(),
            ]);

            try {
                $chunks = $this->embedding->searchByKeyword($question, topK: 5);

                if ($chunks->isNotEmpty()) {
                    $snippets = $chunks->map(
                        fn (KnowledgeBase $kb) => "- {$kb->title}: ".mb_substr($kb->content, 0, 150)
                    )->implode("\n");

                    yield ['text' => "Sistem AI sedang offline. Berikut hasil pencarian keyword yang mungkin relevan:\n\n{$snippets}"];

                    yield [
                        'conversation_id' => $conversationId ?? (string) Str::uuid(),
                        'sources' => $chunks->map(fn (KnowledgeBase $kb) => [
                            'id' => $kb->id,
                            'title' => $kb->title,
                            'snippet' => mb_substr($kb->content, 0, 200),
                        ])->all(),
                    ];

                    return;
                }
            } catch (Throwable $fallbackError) {
                Log::error('pg_trgm fallback juga gagal', [
                    'error' => $fallbackError->getMessage(),
                ]);
            }

            yield ['text' => 'Maaf, layanan AI sedang tidak tersedia. Silakan coba lagi nanti.'];
            yield ['conversation_id' => $conversationId ?? (string) Str::uuid()];
        }
    }

    /**
     * Chat AI RAG flow.
     *
     * 1. Embed question via Gemini
     * 2. Vector search top-5 chunks
     * 3. Generate response via Gemini 2.5 Flash dengan context
     * 4. Fallback ke pg_trgm kalau Gemini fail
     *
     * @param  User|null  $user  User untuk conversation memory
     * @return array{answer: string, sources: array<int, array{id: int, title: string, snippet: string}>, confidence: string, fallback: bool, model: string, conversation_id?: string}
     */
    public function chat(string $question, ?string $conversationId = null, ?User $user = null): array
    {
        $question = trim($question);

        if (mb_strlen($question) < 5 || mb_strlen($question) > 500) {
            throw new BusinessRuleException('Pertanyaan harus 5-500 karakter.');
        }

        try {
            // Tier 1: vector search via Gemini embedding
            $queryEmbedding = $this->embedding->embed($question);
            $chunks = $this->embedding->searchSimilar($queryEmbedding, topK: 5);

            $context = $chunks->map(fn (KnowledgeBase $kb) => [
                'content' => $kb->content,
                'source' => $kb->title.($kb->page_number !== null ? " (chunk #{$kb->page_number})" : ''),
            ])->all();

            $contextText = $this->buildContextSection($context);
            $agentPrompt = <<<PROMPT
KONTEKS:
{$contextText}

PERTANYAAN: {$question}

JAWABAN:
PROMPT;

            $agent = $this->prepareAgent($user, $conversationId);
            $result = $agent->prompt($agentPrompt);

            return [
                'answer' => $result['answer'],
                'sources' => $chunks->map(fn (KnowledgeBase $kb) => [
                    'id' => $kb->id,
                    'title' => $kb->title,
                    'snippet' => mb_substr($kb->content, 0, 200),
                    'page_number' => $kb->page_number,
                    'source_document' => $kb->source_document,
                ])->all(),
                'confidence' => $result['confidence'] ?? 'low',
                'fallback' => false,
                'model' => config('services.gemini.model'),
                'conversation_id' => $result->conversationId ?? $conversationId,
            ];
        } catch (Throwable $e) {
            Log::warning('Gemini RAG flow gagal, fallback ke pg_trgm', [
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackKeywordSearch($question, $conversationId);
        }
    }

    /**
     * Prepare the agent with optional conversation memory.
     */
    protected function prepareAgent(?User $user, ?string $conversationId = null): HrKnowledgeBaseAgent
    {
        $agent = new HrKnowledgeBaseAgent;

        if ($user) {
            if ($conversationId) {
                return $agent->continue($conversationId, as: $user);
            }

            return $agent->forUser($user);
        }

        return $agent;
    }

    /**
     * Fallback kalau Gemini API down — pg_trgm keyword search.
     *
     * @return array{answer: string, sources: array<int, array{id: int, title: string, snippet: string}>, confidence: string, fallback: bool, model: string, conversation_id?: string}
     */
    protected function fallbackKeywordSearch(string $question, ?string $conversationId = null): array
    {
        $chunks = $this->embedding->searchByKeyword($question, topK: 5);

        if ($chunks->isEmpty()) {
            return [
                'answer' => 'Maaf, tidak ada informasi yang cocok dengan pertanyaan Anda di basis data HRConnect saat ini. Sistem AI sedang offline.',
                'sources' => [],
                'confidence' => 'low',
                'fallback' => true,
                'model' => 'pg_trgm',
                'conversation_id' => $conversationId,
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
            'confidence' => 'low',
            'fallback' => true,
            'model' => 'pg_trgm',
            'conversation_id' => $conversationId,
        ];
    }

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

        if (! $owner) {
            throw ValidationException::withMessages([
                'owner' => ['Upload knowledge base membutuhkan owner yang valid.'],
            ]);
        }

        $filename = uniqid('kb_').'.pdf';

        try {
            $path = $pdf->storeAs('knowledgebase', $filename, 'local');
        } catch (Throwable $e) {
            throw new BusinessRuleException('Gagal menyimpan file PDF: '.$e->getMessage());
        }

        if ($path === false) {
            throw new BusinessRuleException('Gagal menyimpan file PDF: penyimpanan tidak merespon.');
        }

        $absolutePath = storage_path('app/'.$path);

        try {
            $rawText = $this->embedding->extractTextFromPdf($absolutePath);
            $chunks = $this->embedding->chunkText($rawText);
        } catch (Throwable $e) {
            Storage::disk('local')->delete('knowledgebase/'.$filename);
            throw new BusinessRuleException('Gagal memproses PDF: '.$e->getMessage());
        }

        if (empty($chunks)) {
            Storage::disk('local')->delete('knowledgebase/'.$filename);
            throw new BusinessRuleException('PDF tidak menghasilkan chunk yang valid.');
        }

        try {
            $createdRecords = DB::transaction(function () use ($chunks, $title, $category, $filename, $owner) {
                $records = [];

                foreach ($chunks as $index => $chunkContent) {
                    $kb = KnowledgeBase::create([
                        'knowledgeable_type' => $owner::class,
                        'knowledgeable_id' => $owner->getKey(),
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
        } catch (Throwable $e) {
            Storage::disk('local')->delete('knowledgebase/'.$filename);
            throw $e;
        }

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
