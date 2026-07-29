<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\EmployeeDocumentRequest;
use App\Services\DocumentTemplateRenderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Throwable;

final class GenerateEmployeeDocumentPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly int $documentRequestId,
    ) {}

    public function handle(DocumentTemplateRenderService $renderer): void
    {
        $request = EmployeeDocumentRequest::with(['documentType.activeTemplate', 'employee'])->findOrFail($this->documentRequestId);

        $template = $request->documentType?->activeTemplate();

        if (! $template) {
            return;
        }

        $html = $renderer->renderHtml($template, $request->employee, $request);

        $pdfContent = $this->htmlToPdf($html);

        $path = 'documents/generated/req-'.$request->id.'-'.time().'.pdf';
        Storage::disk('private')->put($path, $pdfContent);

        $request->update([
            'status' => EmployeeDocumentRequest::STATUS_GENERATED,
            'generated_path' => $path,
            'generated_template_id' => $template->id,
            'generated_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $request = EmployeeDocumentRequest::find($this->documentRequestId);
        $request?->update([
            'status' => EmployeeDocumentRequest::STATUS_PENDING,
            'rejection_note' => 'PDF generation failed: '.($exception?->getMessage() ?? 'unknown'),
        ]);
    }

    /**
     * Konversi HTML ke PDF. Pakai package jika ada, fallback ke HTML polos.
     */
    private function htmlToPdf(string $html): string
    {
        if (class_exists(Pdf::class)) {
            return Pdf::loadHTML($html)->output();
        }

        if (class_exists(Browsershot::class)) {
            return Browsershot::html($html)->pdf();
        }

        // Fallback: simpan HTML (skripsi, belum ada deps PDF)
        return $html;
    }
}
