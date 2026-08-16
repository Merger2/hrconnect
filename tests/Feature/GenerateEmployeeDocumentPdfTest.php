<?php

declare(strict_types=1);

use App\Jobs\GenerateEmployeeDocumentPdf;
use App\Models\Employee;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentTemplate;
use App\Models\EmployeeDocumentType;
use App\Services\DocumentTemplateRenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function documentRequestFixture(): array
{
    $type = EmployeeDocumentType::factory()->create();
    $template = EmployeeDocumentTemplate::factory()->create([
        'document_type_id' => $type->id,
        'content' => '{{ $employee_name }} — {{ $request_purpose }}',
        'is_active' => true,
    ]);
    $employee = Employee::factory()->create();

    $request = EmployeeDocumentRequest::factory()->create([
        'employee_id' => $employee->id,
        'document_type_id' => $type->id,
        'status' => EmployeeDocumentRequest::STATUS_PENDING,
    ]);

    return [$request, $template];
}

test('job generates pdf, stores it and marks request generated', function () {
    Storage::fake('private');

    [$request, $template] = documentRequestFixture();

    (new GenerateEmployeeDocumentPdf($request->id))->handle(app(DocumentTemplateRenderService::class));

    $request->refresh();

    expect($request->status)->toBe(EmployeeDocumentRequest::STATUS_GENERATED)
        ->and($request->generated_path)->not->toBeNull()
        ->and($request->generated_template_id)->toBe($template->id)
        ->and($request->generated_at)->not->toBeNull();

    Storage::disk('private')->assertExists($request->generated_path);
});

test('job is a no-op when document type has no active template', function () {
    Storage::fake('private');

    $type = EmployeeDocumentType::factory()->create();
    $request = EmployeeDocumentRequest::factory()->create([
        'document_type_id' => $type->id,
        'status' => EmployeeDocumentRequest::STATUS_PENDING,
    ]);

    (new GenerateEmployeeDocumentPdf($request->id))->handle(app(DocumentTemplateRenderService::class));

    expect($request->fresh()->status)->toBe(EmployeeDocumentRequest::STATUS_PENDING)
        ->and($request->fresh()->generated_path)->toBeNull();
});

test('job failure resets request to pending with rejection note', function () {
    $request = EmployeeDocumentRequest::factory()->create([
        'status' => EmployeeDocumentRequest::STATUS_PENDING,
    ]);

    (new GenerateEmployeeDocumentPdf($request->id))
        ->failed(new RuntimeException('Disk full'));

    expect($request->fresh()->status)->toBe(EmployeeDocumentRequest::STATUS_PENDING)
        ->and($request->fresh()->rejection_note)->toContain('PDF generation failed')
        ->and($request->fresh()->rejection_note)->toContain('Disk full');
});

test('job can be queued for processing', function () {
    Queue::fake();

    [$request] = documentRequestFixture();

    GenerateEmployeeDocumentPdf::dispatch($request->id);

    Queue::assertPushed(GenerateEmployeeDocumentPdf::class, fn (GenerateEmployeeDocumentPdf $job) => $job->documentRequestId === $request->id);
});
