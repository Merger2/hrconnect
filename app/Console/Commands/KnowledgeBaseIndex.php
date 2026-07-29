<?php

namespace App\Console\Commands;

use App\Models\KnowledgeBase;
use App\Services\KnowledgeBase\KnowledgeBaseService;
use Illuminate\Console\Command;

class KnowledgeBaseIndex extends Command
{
    protected $signature = 'knowledgebase:index
        {--kb-id= : Reindex a specific KnowledgeBase by ID}
        {--all : Reindex all KnowledgeBase entries}';

    protected $description = 'Reindex KnowledgeBase entries (regenerate embedding + pg_trgm)';

    public function handle(KnowledgeBaseService $kbService): int
    {
        try {
            $specificId = $this->option('kb-id');
            $all = $this->option('all');

            if ($specificId) {
                $kb = KnowledgeBase::find($specificId);
                if (! $kb) {
                    $this->error("KnowledgeBase ID {$specificId} tidak ditemukan.");

                    return self::FAILURE;
                }
                $kbService->reindex($kb);
                $this->info("KnowledgeBase ID {$specificId} berhasil di-reindex.");

                return self::SUCCESS;
            }

            if ($all) {
                $count = 0;
                KnowledgeBase::chunk(100, function ($entries) use ($kbService, &$count) {
                    foreach ($entries as $kb) {
                        $kbService->reindex($kb);
                        $count++;
                    }
                });
                $this->info("{$count} KnowledgeBase entries berhasil di-reindex.");

                return self::SUCCESS;
            }

            $this->warn('Gunakan --all atau --kb-id={id} untuk menentukan yang akan di-reindex.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("KnowledgeBase index gagal: {$e->getMessage()}");
            report($e);

            return self::FAILURE;
        }
    }
}
