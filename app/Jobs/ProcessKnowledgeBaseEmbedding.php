<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\KnowledgeBaseStatus;
use App\Models\KnowledgeBase;
use App\Services\Security\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessKnowledgeBaseEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [30, 60, 120];

    public $timeout = 300;

    public function __construct(public KnowledgeBase $knowledgeBase) {}

    public function handle(EmbeddingService $embeddingService): void
    {
        try {
            $embeddingService->processKnowledgeBase($this->knowledgeBase);
        } catch (\Throwable $e) {
            Log::error('Gagal memproses embedding: '.$e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->knowledgeBase->update([
            'status' => KnowledgeBaseStatus::ERROR,
        ]);
    }
}
