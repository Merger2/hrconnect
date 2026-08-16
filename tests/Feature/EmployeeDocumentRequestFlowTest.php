<?php

use App\Livewire\Admin\DocumentTemplateLibrary;
use App\Livewire\Admin\DocumentTemplateManager;
use App\Livewire\Admin\EmployeeDocumentRequestManager;
use App\Livewire\User\EmployeeDocumentRequestPage;
use App\Models\Employee;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentTemplate;
use App\Models\EmployeeDocumentType;
use App\Models\User;
use App\Notifications\DocumentRequestReadyNotification;
use Database\Seeders\EmployeeDocumentTemplateSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function makeDocType(array $overrides = []): EmployeeDocumentType
{
    return EmployeeDocumentType::create(array_merge([
        'name' => 'Surat '.uniqid(),
        'slug' => 'type_'.uniqid(),
        'code' => 'code_'.uniqid(),
        'category' => 'hr',
        'is_active' => true,
        'admin_requestable' => true,
        'requires_employee_upload' => false,
        'auto_generate_enabled' => false,
    ], $overrides));
}

test('employee submits a document request for admin fulfillment', function () {
    $type = makeDocType();
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(EmployeeDocumentRequestPage::class)
        ->call('create')
        ->set('documentType', (string) $type->id)
        ->set('purpose', 'Bank account opening requirement.')
        ->set('details', 'Please include job title and active employment status.')
        ->call('store')
        ->assertHasNoErrors();

    $request = EmployeeDocumentRequest::query()->first();

    expect($request)->not->toBeNull()
        ->and($request->employee_id)->toBe($employee->id)
        ->and($request->document_type_id)->toBe($type->id)
        ->and($request->purpose)->toBe('Bank account opening requirement.')
        ->and($request->status)->toBe(EmployeeDocumentRequest::STATUS_PENDING);
});

test('admin marks a document request as ready and notifies employee', function () {
    Notification::fake();

    $admin = User::factory()->admin(true)->create();
    $employee = User::factory()->create();
    Employee::factory()->create(['user_id' => $employee->id]);
    $type = makeDocType(['code' => 'npwp']);
    $request = EmployeeDocumentRequest::create([
        'employee_id' => $employee->employee->id,
        'document_type_id' => $type->id,
        'request_source' => 'employee',
        'purpose' => 'Apartment rental verification.',
        'status' => EmployeeDocumentRequest::STATUS_PENDING,
    ]);

    $this->actingAs($admin);

    Livewire::test(EmployeeDocumentRequestManager::class)
        ->call('confirmReady', $request->id)
        ->set('reviewNote', 'Ready for pickup at HR desk.')
        ->call('markReady')
        ->assertHasNoErrors();

    $request->refresh();

    expect($request->status)->toBe(EmployeeDocumentRequest::STATUS_READY)
        ->and($request->reviewed_by)->toBe($admin->id)
        ->and($request->reviewed_at)->not->toBeNull()
        ->and($request->fulfillment_note)->toBe('Ready for pickup at HR desk.')
        ->and($request->rejection_note)->toBeNull();

    Notification::assertSentTo($employee, DocumentRequestReadyNotification::class);
});

test('admin requests an employee upload and employee submits private document', function () {
    Storage::fake('private');

    $admin = User::factory()->admin(true)->create();
    $employee = User::factory()->create();
    Employee::factory()->create(['user_id' => $employee->id]);
    $type = makeDocType(['code' => 'npwp', 'requires_employee_upload' => true]);

    $this->actingAs($admin);

    Livewire::test(EmployeeDocumentRequestManager::class)
        ->call('createRequest')
        ->set('targetUserIds', [$employee->employee->id])
        ->set('documentType', $type->id)
        ->set('purpose', 'Please upload NPWP for payroll tax data.')
        ->set('details', 'Finance needs the latest NPWP file.')
        ->set('dueDate', now()->addWeek()->toDateString())
        ->call('storeRequest')
        ->assertHasNoErrors();

    $request = EmployeeDocumentRequest::query()->firstOrFail();
    expect($request->request_source)->toBe('admin')
        ->and($request->status)->toBe(EmployeeDocumentRequest::STATUS_REQUESTED)
        ->and($request->due_date?->toDateString())->toBe(now()->addWeek()->toDateString());

    $this->actingAs($employee);

    Livewire::test(EmployeeDocumentRequestPage::class)
        ->call('prepareUpload', $request->id)
        ->set('attachment', UploadedFile::fake()->create('npwp.pdf', 100, 'application/pdf'))
        ->call('upload')
        ->assertHasNoErrors();

    $request->refresh();
    expect($request->status)->toBe(EmployeeDocumentRequest::STATUS_UPLOADED)
        ->and($request->uploaded_path)->not->toBeNull();

    Storage::disk('private')->assertExists($request->uploaded_path);
});

test('admin generates a document from settings template', function () {
    Storage::fake('private');

    $admin = User::factory()->admin(true)->create();
    $employee = User::factory()->create(['name' => 'Nadisha']);
    Employee::factory()->create(['user_id' => $employee->id]);
    $type = makeDocType(['code' => 'employment_certificate', 'auto_generate_enabled' => true]);
    EmployeeDocumentTemplate::create([
        'document_type_id' => $type->id,
        'name' => 'Default SKK',
        'content' => '<h2>Surat Keterangan Kerja</h2><p>{{ employee_name }} - {{ request_purpose }}</p>',
        'is_active' => true,
    ]);
    $request = EmployeeDocumentRequest::create([
        'employee_id' => $employee->employee->id,
        'document_type_id' => $type->id,
        'request_source' => 'employee',
        'purpose' => 'Bank account opening.',
        'status' => EmployeeDocumentRequest::STATUS_PENDING,
    ]);

    $this->actingAs($admin);

    Livewire::test(EmployeeDocumentRequestManager::class)
        ->call('generate', $request->id)
        ->assertHasNoErrors();

    $request->refresh();
    expect($request->status)->toBe(EmployeeDocumentRequest::STATUS_GENERATED)
        ->and($request->generated_path)->not->toBeNull()
        ->and($request->generated_template_id)->not->toBeNull();

    Storage::disk('private')->assertExists($request->generated_path);
    expect(Storage::disk('private')->get($request->generated_path))->toContain('%PDF');
});

test('document ready notification is sent with mail payload', function () {
    $employee = User::factory()->create();
    Employee::factory()->create(['user_id' => $employee->id]);
    $type = makeDocType();
    $request = EmployeeDocumentRequest::create([
        'employee_id' => $employee->employee->id,
        'document_type_id' => $type->id,
        'request_source' => 'employee',
        'purpose' => 'Bank account opening.',
        'status' => EmployeeDocumentRequest::STATUS_READY,
    ]);

    $mail = (new DocumentRequestReadyNotification($request))->toMail($employee);

    expect($mail->subject)->toBe(__('Document Ready'));
});

test('document template manager keeps one active template per document type', function () {
    $admin = User::factory()->admin(true)->create();
    $type = makeDocType(['code' => 'bank_letter']);
    $oldTemplate = EmployeeDocumentTemplate::create([
        'document_type_id' => $type->id,
        'name' => 'Old Template',
        'content' => '<p>Old</p>',
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(DocumentTemplateManager::class)
        ->set('editorMode', 'html')
        ->set('documentTemplateForm.document_type_id', $type->id)
        ->set('documentTemplateForm.name', 'New Template')
        ->set('documentTemplateForm.body', '<p>{{ employee.name }}</p>')
        ->set('documentTemplateForm.paper_size', 'a4')
        ->set('documentTemplateForm.orientation', 'portrait')
        ->set('documentTemplateForm.is_active', true)
        ->call('saveDocumentTemplate')
        ->assertHasNoErrors();

    $newTemplate = EmployeeDocumentTemplate::query()->where('name', 'New Template')->firstOrFail();
    expect($newTemplate->is_active)->toBeTrue()
        ->and($oldTemplate->refresh()->is_active)->toBeFalse();

    Livewire::test(DocumentTemplateLibrary::class)
        ->call('duplicateTemplate', $newTemplate->id)
        ->assertHasNoErrors();

    expect(EmployeeDocumentTemplate::query()
        ->where('name', __('Copy of :name', ['name' => 'New Template']))
        ->where('is_active', false)
        ->exists())->toBeTrue();

    $request = EmployeeDocumentRequest::create([
        'employee_id' => Employee::factory()->create()->id,
        'document_type_id' => $type->id,
        'request_source' => 'admin',
        'purpose' => 'Audit trail.',
        'status' => EmployeeDocumentRequest::STATUS_GENERATED,
        'generated_template_id' => $newTemplate->id,
    ]);

    Livewire::test(DocumentTemplateLibrary::class)
        ->call('confirmDeleteTemplate', $newTemplate->id)
        ->call('deleteTemplate')
        ->assertHasNoErrors();

    expect($request->refresh()->generated_template_id)->toBe($newTemplate->id)
        ->and($newTemplate->refresh()->is_active)->toBeFalse();
});

test('document template seeder creates two templates for every default document type', function () {
    $this->seed(EmployeeDocumentTemplateSeeder::class);

    $types = EmployeeDocumentType::query()
        ->withCount('templates')
        ->get();

    // Migration default (6) + seeder letter types — minimal 7 tipe default.
    expect($types->count())->toBeGreaterThanOrEqual(7);

    foreach ($types as $type) {
        expect($type->templates_count)->toBeGreaterThanOrEqual(2)
            ->and($type->templates()->where('is_active', true)->count())->toBe(1);
    }
});

test('admin can create and immediately generate an auto template document request', function () {
    Storage::fake('private');

    $admin = User::factory()->admin(true)->create();
    $employeeUser = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $employeeUser->id]);
    $type = makeDocType(['code' => 'employment_letter_auto', 'auto_generate_enabled' => true]);
    EmployeeDocumentTemplate::create([
        'document_type_id' => $type->id,
        'name' => 'Active Letter',
        'content' => '<p>{{ employee_name }} - {{ request_purpose }}</p>',
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(EmployeeDocumentRequestManager::class)
        ->call('createRequest')
        ->set('targetUserIds', [$employee->id])
        ->set('documentType', $type->id)
        ->set('purpose', 'Bank account opening.')
        ->set('generateImmediately', true)
        ->call('storeRequest')
        ->assertHasNoErrors();

    $request = EmployeeDocumentRequest::query()->where('document_type_id', $type->id)->firstOrFail();

    expect($request->status)->toBe(EmployeeDocumentRequest::STATUS_GENERATED)
        ->and($request->generated_path)->not->toBeNull();

    Storage::disk('private')->assertExists($request->generated_path);
});

test('admin can create document requests for multiple employees and process them in bulk', function () {
    Storage::fake('private');

    $admin = User::factory()->admin(true)->create();
    $employees = Employee::factory()->count(2)->create();
    $type = makeDocType(['code' => 'bulk_letter']);
    EmployeeDocumentTemplate::create([
        'document_type_id' => $type->id,
        'name' => 'Bulk Letter Active',
        'content' => '<p>{{ employee_name }} - {{ request_purpose }}</p>',
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(EmployeeDocumentRequestManager::class)
        ->call('createRequest')
        ->set('targetUserIds', $employees->pluck('id')->map(fn ($id) => (int) $id)->all())
        ->set('documentType', $type->id)
        ->set('purpose', 'Mass bank administration.')
        ->call('storeRequest')
        ->assertHasNoErrors();

    $requests = EmployeeDocumentRequest::query()->where('document_type_id', $type->id)->get();
    expect($requests)->toHaveCount(2)
        ->and($requests->every(fn ($r) => $r->status === EmployeeDocumentRequest::STATUS_PENDING))->toBeTrue();

    Livewire::test(EmployeeDocumentRequestManager::class)
        ->set('selectedRequestIds', $requests->pluck('id')->map(fn ($id) => (int) $id)->all())
        ->call('bulkGenerate')
        ->assertHasNoErrors();

    $requests->each(function (EmployeeDocumentRequest $request): void {
        $request->refresh();
        expect($request->status)->toBe(EmployeeDocumentRequest::STATUS_GENERATED)
            ->and($request->generated_path)->not->toBeNull();
        Storage::disk('private')->assertExists($request->generated_path);
    });
});

test('admin rejects a document request and stores rejection note', function () {
    $admin = User::factory()->admin(true)->create();
    $employeeUser = User::factory()->create();
    Employee::factory()->create(['user_id' => $employeeUser->id]);
    $type = makeDocType(['code' => 'visa_letter']);
    $request = EmployeeDocumentRequest::create([
        'employee_id' => $employeeUser->employee->id,
        'document_type_id' => $type->id,
        'request_source' => 'employee',
        'purpose' => 'Travel visa application.',
        'status' => EmployeeDocumentRequest::STATUS_PENDING,
    ]);

    $this->actingAs($admin);

    Livewire::test(EmployeeDocumentRequestManager::class)
        ->call('confirmReject', $request->id)
        ->set('reviewNote', 'Please update your address profile first.')
        ->call('reject')
        ->assertHasNoErrors();

    $request->refresh();

    expect($request->status)->toBe(EmployeeDocumentRequest::STATUS_REJECTED)
        ->and($request->reviewed_by)->toBe($admin->id)
        ->and($request->reviewed_at)->not->toBeNull()
        ->and($request->rejection_note)->toBe('Please update your address profile first.');
});
