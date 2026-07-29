<?php

namespace App\Console\Commands;

use App\Models\ImportExportRun;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('import-export-runs:prune-expired {--hours=12 : Prune records older than this many hours}')]
#[Description('Prune expired/completed import/export runs older than N hours')]
class ImportExportRunsPruneExpired extends Command
{
    public function handle(): int
    {
        try {
            $hours = (int) $this->option('hours');
            $cutoff = now()->subHours($hours);

            $deleted = ImportExportRun::where(function ($query) use ($cutoff): void {
                $query->where('completed_at', '<', $cutoff)
                    ->orWhere('failed_at', '<', $cutoff);
            })
                ->whereIn('status', ['completed', 'failed'])
                ->delete();

            $this->info("Deleted {$deleted} expired import/export job(s).");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Prune import/export runs gagal: {$e->getMessage()}");
            report($e);

            return self::FAILURE;
        }
    }
}
