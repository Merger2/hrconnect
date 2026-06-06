<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\KnowledgeBaseStatus;
use App\Models\KnowledgeBase;
use App\Services\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ProcessKnowledgeBaseEmbedding — async job untuk generate embedding KB chunk.
 *
 * Dispatched dari KnowledgeBaseService::uploadPdf() per chunk.
 *
 * Config (per PRD §16):
 * - Queue: default
 * - Tries: 3
 * - Timeout: 300s
 * - Backoff: [30, 60, 120]
 *
 * Saat job gagal final → KB record di-set status=ERROR (bisa di-reindex manual).
 */
class ProcessKnowledgeBaseEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public function __construct(
        public KnowledgeBase $knowledgeBase,
    ) {
        $this->onQueue('default');
    }

    public function handle(EmbeddingService $embeddingService): void
    {
        Log::info('Generating KB embedding', [
            'kb_id' => $this->knowledgeBase->id,
            'title' => $this->knowledgeBase->title,
            'chunk' => $this->knowledgeBase->page_number,
        ]);

        $embeddingService->processKnowledgeBase($this->knowledgeBase);

        Log::info('KB embedding generated', ['kb_id' => $this->knowledgeBase->id]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('KB embedding job failed permanently', [
            'kb_id' => $this->knowledgeBase->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->knowledgeBase->update(['status' => KnowledgeBaseStatus::ERROR]);
    }
}
