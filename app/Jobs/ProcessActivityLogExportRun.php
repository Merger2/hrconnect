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
            'row_count' => $rowCount,
            'completed_at' => now(),
        ]);
    }

    private function exportData(ImportExportRun $run, array $meta): array
    {
        $query = ActivityLog::query()
            ->with(['causer', 'subject'])
            ->orderBy('created_at', 'desc');

        if (! empty($meta['start_date'])) {
            $query->whereDate('created_at', '>=', $meta['start_date']);
        }
        if (! empty($meta['end_date'])) {
            $query->whereDate('created_at', '<=', $meta['end_date']);
        }
        if (! empty($meta['causer_id'])) {
            $query->where('causer_id', $meta['causer_id']);
        }
        if (! empty($meta['subject_type'])) {
            $query->where('subject_type', $meta['subject_type']);
        }

        $records = $query->get();

        return [$records, $records->count()];
    }
}
