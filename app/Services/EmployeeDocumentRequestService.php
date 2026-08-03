<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\GenerateEmployeeDocumentPdf;
use App\Models\Employee;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentType;
use App\Models\User;
use App\Notifications\DocumentRequestReadyNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class EmployeeDocumentRequestService
{
    public function __construct(
        private readonly DocumentTemplateRenderService $renderer,
    ) {}

    /**
     * Get document types as key-value pairs for select dropdown.
     */
    public function types(): array
    {
        return EmployeeDocumentType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Simplified request method for the user Livewire component.
     * Resolves document_type string to ID, creates via createForEmployee.
     */
    public function request(User $user, array $data): EmployeeDocumentRequest
    {
        $employee = $user->employee;

        if (! $employee) {
            throw new \RuntimeException('User has no associated employee record.');
        }

        // Resolve document_type (could be ID or slug/name)
        $typeId = $data['document_type'];
        if (! is_numeric($typeId)) {
            $type = EmployeeDocumentType::where('slug', $typeId)
                ->orWhere('name', $typeId)
                ->firstOrFail();
            $typeId = $type->id;
        }

        return $this->createForEmployee($employee, [
            'document_type_id' => (int) $typeId,
            'purpose' => $data['purpose'] ?? $data['document_type'],
            'details' => $data['details'] ?? null,
            'due_date' => $data['due_date'] ?? null,
        ]);
    }

    /**
     * Employee bikin request baru.
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeDocumentRequest
    {
        $type = EmployeeDocumentType::findOrFail($data['document_type_id']);

        $request = EmployeeDocumentRequest::create([
            'employee_id' => $employee->id,
            'document_type_id' => $type->id,
            'requested_by' => Auth::id(),
            'request_source' => 'employee',
            'purpose' => $data['purpose'],
            'details' => $data['details'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'status' => $type->requires_employee_upload
                ? EmployeeDocumentRequest::STATUS_REQUESTED
                : EmployeeDocumentRequest::STATUS_PENDING,
        ]);

        // Langsung generate kalau template aktif & auto_generate
        if (! $type->requires_employee_upload && $type->auto_generate_enabled && $type->activeTemplate()) {
            GenerateEmployeeDocumentPdf::dispatch($request->id);
        }

        return $request;
    }

    /**
     * Admin bikin request atas nama karyawan.
     */
    public function createForEmployeeByAdmin(Employee $employee, array $data, bool $generateImmediately = false): EmployeeDocumentRequest
    {
        $type = EmployeeDocumentType::findOrFail($data['document_type_id']);

        $request = EmployeeDocumentRequest::create([
            'employee_id' => $employee->id,
            'document_type_id' => $type->id,
            'requested_by' => Auth::id(),
            'request_source' => 'admin',
            'purpose' => $data['purpose'],
            'details' => $data['details'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'status' => $type->requires_employee_upload
                ? EmployeeDocumentRequest::STATUS_REQUESTED
                : EmployeeDocumentRequest::STATUS_PENDING,
        ]);

        if (($generateImmediately || $type->auto_generate_enabled) && $type->activeTemplate()) {
            GenerateEmployeeDocumentPdf::dispatch($request->id);
        }

        return $request;
    }

    /**
     * Employee upload file (status requested/upload_processing -> uploaded).
     */
    public function upload(EmployeeDocumentRequest $request, $file): void
    {
        Gate::authorize('upload', $request);

        $path = $file->store('documents/uploads', 'private');

        $request->update([
            'status' => EmployeeDocumentRequest::STATUS_UPLOADED,
            'uploaded_path' => $path,
            'uploaded_original_name' => $file->getClientOriginalName(),
            'uploaded_at' => now(),
        ]);

        // Notifikasi ke admin/HR
        if ($request->documentType?->admin_requestable) {
            // notification ke role HR bisa ditambah di sini
        }
    }

    /**
     * Admin generate PDF dari template aktif.
     */
    public function generate(EmployeeDocumentRequest $request): void
    {
        Gate::authorize('generate', $request);

        $template = $request->documentType?->activeTemplate();

        if (! $template) {
            throw new \RuntimeException('No active template for this document type.');
        }

        GenerateEmployeeDocumentPdf::dispatch($request->id);
    }

    /**
     * Admin approve / mark ready.
     */
    public function approve(EmployeeDocumentRequest $request, ?string $note = null): void
    {
        Gate::authorize('fulfill', $request);

        $request->update([
            'status' => EmployeeDocumentRequest::STATUS_READY,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'fulfillment_note' => $note,
        ]);

        if ($request->employee?->user) {
            $request->employee->user->notify(new DocumentRequestReadyNotification($request));
        }
    }

    /**
     * Admin reject.
     */
    public function reject(EmployeeDocumentRequest $request, string $reason): void
    {
        Gate::authorize('reject', $request);

        $request->update([
            'status' => EmployeeDocumentRequest::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_note' => $reason,
        ]);
    }

    /**
     * Download generated PDF.
     */
    public function downloadGenerated(EmployeeDocumentRequest $request)
    {
        Gate::authorize('download', $request);

        abort_if(blank($request->generated_path), 404);

        return Storage::disk('private')->download($request->generated_path, $request->documentType?->name.'.pdf');
    }

    /**
     * Download uploaded file.
     */
    public function downloadUploaded(EmployeeDocumentRequest $request)
    {
        Gate::authorize('downloadUpload', $request);

        abort_if(blank($request->uploaded_path), 404);

        return Storage::disk('private')->download($request->uploaded_path, $request->uploaded_original_name ?? 'document');
    }
}
