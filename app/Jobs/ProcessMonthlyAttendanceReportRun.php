<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exports\AttendanceReportExport;
use App\Models\Attendance;
use App\Models\ImportExportRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Facades\Excel;

class ProcessMonthlyAttendanceReportRun implements ShouldQueue
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

        $fileName = "monthly-attendance-report-{$run->id}.xlsx";
        $path = "exports/{$fileName}";

        Excel::store(new AttendanceReportExport($data), $path, 'local');

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

        if (! empty($meta['month'])) {
            $query->where('month', $meta['month']);
        }
        if (! empty($meta['year'])) {
            $query->where('year', $meta['year']);
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
