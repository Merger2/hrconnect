<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\EmployeeDocumentRequest;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessEmployeeDocumentUpload implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = 30;

    public $timeout = 120;

    public function __construct(
        public int $documentId,
        public string $disk = 'local'
    ) {}

    public function handle(): void
    {
        $document = EmployeeDocumentRequest::query()->findOrFail($this->documentId);

        try {
            // Validate file exists
            if (! Storage::disk($this->disk)->exists($document->file_path)) {
                throw new Exception('File not found on storage: '.$document->file_path);
            }

            // Get file content to determine mime type
            $filePath = Storage::disk($this->disk)->path($document->file_path);
            $mimeType = File::mimeType($filePath) ?? 'application/octet-stream';

            // Update document status
            $document->update([
                'status' => 'processed',
                'processed_at' => now(),
                'file_size' => Storage::disk($this->disk)->size($document->file_path),
                'mime_type' => $mimeType,
            ]);

            Log::info('Employee document processed', [
                'document_id' => $document->id,
                'employee_id' => $document->employee_id,
            ]);
        } catch (Throwable $e) {
            Log::error('Employee document processing failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            $document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
