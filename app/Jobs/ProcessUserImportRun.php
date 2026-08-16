<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Imports\UserImport;
use App\Models\ImportExportRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ProcessUserImportRun implements ShouldQueue
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
            $import = new UserImport;
            Excel::import($import, $sourcePath, 'local');

            $rowCount = $import->getRowCount();
            $errors = $import->getErrors();

            // Mock-miss fix (2026-08-16): baris yang gagal sebelumnya hanya
            // masuk Log::error dan run tetap dilaporkan `completed` tanpa jejak
            // → partial failure senyap di UI. Kini error dipersist ke
            // meta.errors + error_message (ditampilkan run-list) dan total_rows
            // mencerminkan seluruh baris (sukses + gagal).
            $run->update([
                'status' => 'completed',
                'total_rows' => $rowCount + count($errors),
                'processed_rows' => $rowCount,
                'meta' => array_merge($run->meta ?? [], [
                    'successful_rows' => $rowCount,
                    'skipped_rows' => count($errors),
                    'errors' => array_slice($errors, 0, 20),
                ]),
                'error_message' => $errors !== []
                    ? count($errors).' baris gagal diimpor (lihat detail error).'
                    : null,
                'completed_at' => now(),
            ]);
        } catch (ValidationException $e) {
            $run->update([
                'status' => 'failed',
                'error_message' => 'Validation errors: '.$e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('User import failed', [
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
