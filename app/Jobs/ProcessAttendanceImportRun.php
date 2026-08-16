<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Imports\AttendanceImport;
use App\Models\ImportExportRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ProcessAttendanceImportRun implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = 30;

    public $timeout = 120;

    public function __construct(public int $runId) {}

    public function handle(): void
    {
        $run = ImportExportRun::query()->findOrFail($this->runId);

        // File input disimpan service di `source_path` (bukan `file_path` —
        // kolom itu dipakai export utk output). Pakai source_path agar import
        // tidak selalu gagal 'Import file not found'.
        $sourcePath = $run->source_path;

        if (! $sourcePath || ! Storage::disk('local')->exists($sourcePath)) {
            $run->update(['status' => 'failed', 'error_message' => 'Import file not found']);

            return;
        }

        try {
            $import = new AttendanceImport;
            Excel::import($import, $sourcePath, 'local');

            $rowCount = $import->getRowCount();

            $run->update([
                'status' => 'completed',
                'total_rows' => $rowCount,
                'processed_rows' => $rowCount,
                'completed_at' => now(),
            ]);
        } catch (ValidationException $e) {
            $run->update([
                'status' => 'failed',
                'error_message' => 'Validation errors: '.$e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Attendance import failed', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $run->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
