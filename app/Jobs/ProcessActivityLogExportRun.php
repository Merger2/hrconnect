<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exports\ActivityLogExport;
use App\Models\ActivityLog;
use App\Models\ImportExportRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Facades\Excel;

class ProcessActivityLogExportRun implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = 30;

    public $timeout = 120;

    public function __construct(public int $runId) {}

    public function handle(): void
    {
        $run = ImportExportRun::query()->findOrFail($this->runId);
        $meta = $run->meta ?? [];

        [$data, $rowCount] = $this->exportData($run, $meta);

        $fileName = "activity-log-export-{$run->id}.xlsx";
        $path = "exports/{$fileName}";

        Excel::store(new ActivityLogExport($data), $path, 'local');

        $run->update([
            'status' => 'completed',
            'file_path' => $path,
            'total_rows' => $rowCount,
            'processed_rows' => $rowCount,
            'completed_at' => now(),
        ]);
    }

    private function exportData(ImportExportRun $run, array $meta): array
    {
        // Tabel activity_logs pakai kolom user_id (bukan skema spatie causer_id/subject_*).
        $query = ActivityLog::query()
            ->with('user')
            ->orderBy('created_at', 'desc');

        if (! empty($meta['start_date'])) {
            $query->whereDate('created_at', '>=', $meta['start_date']);
        }
        if (! empty($meta['end_date'])) {
            $query->whereDate('created_at', '<=', $meta['end_date']);
        }
        if (! empty($meta['user_id'])) {
            $query->where('user_id', $meta['user_id']);
        }

        $records = $query->get();

        return [$records, $records->count()];
    }
}
