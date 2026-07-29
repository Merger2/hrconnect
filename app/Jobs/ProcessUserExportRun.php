<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exports\UserExport;
use App\Models\ImportExportRun;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Facades\Excel;

class ProcessUserExportRun implements ShouldQueue
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

        $fileName = "user-export-{$run->id}.xlsx";
        $path = "exports/{$fileName}";

        Excel::store(new UserExport($data), $path, 'local');

        $run->update([
            'status' => 'completed',
            'file_path' => $path,
            'row_count' => $rowCount,
            'completed_at' => now(),
        ]);
    }

    private function exportData(ImportExportRun $run, array $meta): array
    {
        $query = User::query()
            ->with(['employee', 'roles'])
            ->orderBy('name');

        if (! empty($meta['role'])) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $meta['role']));
        }
        if (! empty($meta['status'])) {
            $query->whereHas('employee', fn ($q) => $q->where('status', $meta['status']));
        }

        $records = $query->get();

        return [$records, $records->count()];
    }
}
