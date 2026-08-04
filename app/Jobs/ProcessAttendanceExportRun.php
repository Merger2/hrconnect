<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exports\AttendanceExport;
use App\Models\Attendance;
use App\Models\ImportExportRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Facades\Excel;

class ProcessAttendanceExportRun implements ShouldQueue
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

        $fileName = "attendance-export-{$run->id}.xlsx";
        $path = "exports/{$fileName}";

        Excel::store(new AttendanceExport($data), $path, 'local');

        $run->update([
            'status' => 'completed',
            'file_path' => $path,
            'row_count' => $rowCount,
            'completed_at' => now(),
        ]);
    }

    private function exportData(ImportExportRun $run, array $meta): array
    {
        $query = Attendance::query()
            ->with(['employee.user', 'employee.position', 'employee.division'])
            ->orderBy('date', 'desc');

        if (! empty($meta['start_date'])) {
            $query->whereDate('date', '>=', $meta['start_date']);
        }
        if (! empty($meta['end_date'])) {
            $query->whereDate('date', '<=', $meta['end_date']);
        }
        if (! empty($meta['employee_id'])) {
            $query->where('employee_id', $meta['employee_id']);
        }
        if (! empty($meta['division_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('division_id', $meta['division_id']));
        }

        $records = $query->get();

        return [$records, $records->count()];
    }
}
