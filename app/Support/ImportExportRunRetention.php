<?php

namespace App\Support;

use App\Models\ImportExportRun;
use Illuminate\Support\Carbon;

class ImportExportRunRetention
{
    public function filterVisible(array $runs): array
    {
        $retentionHours = 12;

        return array_filter($runs, function (array $run) use ($retentionHours): bool {
            if ($run['status'] !== 'completed' && $run['status'] !== 'failed') {
                return true;
            }

            $completedAt = $run['completed_at'] ?? $run['failed_at'] ?? null;

            if (! $completedAt && isset($run['id'])) {
                $model = ImportExportRun::query()->find($run['id']);
                $completedAt = $model?->completed_at ?? $model?->failed_at;
            }

            if (! $completedAt) {
                return true;
            }

            return Carbon::parse($completedAt)->gt(now()->subHours($retentionHours));
        });
    }
}
