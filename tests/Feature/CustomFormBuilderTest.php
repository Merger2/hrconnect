<?php

use App\Livewire\Admin\CustomFormManager;
use App\Livewire\User\MyCustomForms;
use App\Models\CustomFormSubmission;
use App\Models\CustomFormTemplate;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Role;
use App\Models\User;
use App\Support\CustomFormBuilderService;
use App\Support\MultiCompanyService;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('admin can create custom form template and user can submit response', function () {
    $superadmin = User::factory()->admin(true)->create();
    $company = app(MultiCompanyService::class)->createCompany('PT Custom Forms');
    $employee = User::factory()->create(['company_id' => $company->id]);

    // Guard in-component MyCustomForms::mount mensyaratkan employee record.
    Employee::factory()->create(['user_id' => $employee->id, 'company_id' => $company->id]);

    $this->actingAs($superadmin);

    Livewire::test(CustomFormManager::class)
        ->set('templateCompanyId', (string) $company->id)
        ->set('templateTitle', 'Visit Report')
        ->set('templateCategory', 'operations')
        ->set('fieldLines', "Nama Lokasi|text|required\nJenis Kunjungan|select|required|Audit,Instalasi\nCatatan|textarea|optional")
        ->call('createTemplate')
        ->assertHasNoErrors();

    $template = CustomFormTemplate::query()->firstOrFail();

    $this->actingAs($employee);

    Livewire::test(MyCustomForms::class)
        ->call('selectTemplate', $template->id)
        ->set('responseValues.nama_lokasi', 'Gudang Bandung')
        ->set('responseValues.jenis_kunjungan', 'Audit')
        ->set('responseValues.catatan', 'Aman')
        ->call('submit')
        ->assertHasNoErrors();

    $submission = CustomFormSubmission::query()->firstOrFail();

    expect($template->company_id)->toBe($company->id)
        ->and($template->fields)->toHaveCount(3)
        ->and($submission->company_id)->toBe($company->id)
        ->and($submission->submitted_by)->toBe($employee->id)
        ->and($submission->payload['nama_lokasi'])->toBe('Gudang Bandung')
        ->and($submission->payload['jenis_kunjungan'])->toBe('Audit');
});

test('custom form submission can automatically create operational task', function () {
    $superadmin = User::factory()->admin(true)->create();
    $company = app(MultiCompanyService::class)->createCompany('PT Form Automation');
    $employee = User::factory()->create(['company_id' => $company->id]);

    // Guard in-component MyCustomForms::mount mensyaratkan employee record.
    Employee::factory()->create(['user_id' => $employee->id, 'company_id' => $company->id]);

    $project = Project::query()->create([
        'company_id' => $company->id,
        'name' => 'Visit Follow-up Project',
        'status' => Project::STATUS_ACTIVE,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(CustomFormManager::class)
        ->set('templateCompanyId', (string) $company->id)
        ->set('templateTitle', 'Visit Follow-up')
        ->set('templateCategory', 'operations')
        ->set('fieldLines', "Nama Lokasi|text|required\nCatatan|textarea|required")
        ->set('automationEnabled', true)
        ->set('automationProjectId', (string) $project->id)
        ->set('automationTaskTitle', 'Review visit follow-up')
        ->set('automationTaskPriority', ProjectTask::PRIORITY_HIGH)
        ->call('createTemplate')
        ->assertHasNoErrors();

    $template = CustomFormTemplate::query()->firstOrFail();

    $this->actingAs($employee);

    Livewire::test(MyCustomForms::class)
        ->call('selectTemplate', $template->id)
        ->set('responseValues.nama_lokasi', 'Site A')
        ->set('responseValues.catatan', 'Butuh follow-up teknisi')
        ->call('submit')
        ->assertHasNoErrors();

    $submission = CustomFormSubmission::query()->firstOrFail();
    $task = ProjectTask::query()->firstOrFail();
    $notification = $employee->notifications()->firstOrFail();

    expect($template->metadata['automation']['project_id'])->toBe($project->id)
        ->and($submission->metadata['automation_task_id'])->toBe($task->id)
        ->and($task->project_id)->toBe($project->id)
        ->and($task->assigned_to)->toBe($employee->id)
        ->and($task->priority)->toBe(ProjectTask::PRIORITY_HIGH)
        ->and($task->metadata['custom_form_submission_id'])->toBe($submission->id)
        ->and($notification->data['type'])->toBe('project_task_assigned_from_form')
        ->and($notification->data['task_id'])->toBe($task->id)
        ->and($notification->data['url'])->toBe(route('my-tasks', absolute: false));
});

test('custom form submission notifies company scoped reviewers', function () {
    $superadmin = User::factory()->admin(true)->create();
    $companyA = app(MultiCompanyService::class)->createCompany('PT Form Review A');
    $companyB = app(MultiCompanyService::class)->createCompany('PT Form Review B');
    $employee = User::factory()->create(['company_id' => $companyA->id]);

    // Guard in-component MyCustomForms::mount mensyaratkan employee record.
    Employee::factory()->create(['user_id' => $employee->id, 'company_id' => $companyA->id]);

    $reviewerA = User::factory()->admin()->create(['company_id' => $companyA->id]);
    $reviewerB = User::factory()->admin()->create(['company_id' => $companyB->id]);

    $role = Role::query()->create([
        'name' => 'Custom Form Reviewer',
        'slug' => 'custom_form_reviewer',
        'permission_keys' => ['admin.custom_forms.view'],
    ]);
    $reviewerA->roles()->sync([$role->id]);
    $reviewerB->roles()->sync([$role->id]);

    $template = app(CustomFormBuilderService::class)->createTemplate($superadmin, [
        'company_id' => $companyA->id,
        'title' => 'Incident Report',
        'category' => 'ops',
        'field_lines' => 'Incident|text|required',
    ]);

    $this->actingAs($employee);

    Livewire::test(MyCustomForms::class)
        ->call('selectTemplate', $template->id)
        ->set('responseValues.incident', 'Lampu gudang mati')
        ->call('submit')
        ->assertHasNoErrors();

    $reviewerNotification = $reviewerA->notifications()->firstOrFail();

    expect($reviewerNotification->data['type'])->toBe('custom_form_submitted_for_review')
        ->and($reviewerNotification->data['url'])->toBe(route('admin.custom-forms', absolute: false))
        ->and($reviewerB->notifications()->count())->toBe(0)
        ->and($employee->notifications()->where('data->type', 'custom_form_submitted_for_review')->count())->toBe(0);
});

test('custom forms are company scoped for template creation and submission', function () {
    $admin = User::factory()->admin()->create();
    $companyA = app(MultiCompanyService::class)->createCompany('PT Forms A', $admin);
    $companyB = app(MultiCompanyService::class)->createCompany('PT Forms B');
    $employeeA = User::factory()->create(['company_id' => $companyA->id]);

    // Guard in-component MyCustomForms::mount mensyaratkan employee record.
    Employee::factory()->create(['user_id' => $employeeA->id, 'company_id' => $companyA->id]);

    $role = Role::query()->create([
        'name' => 'Form Manager',
        'slug' => 'form_manager',
        'permission_keys' => ['admin.custom_forms.view', 'admin.custom_forms.manage'],
    ]);
    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin->fresh());

    Livewire::test(CustomFormManager::class)
        ->set('templateCompanyId', (string) $companyB->id)
        ->set('templateTitle', 'Cross company form')
        ->set('fieldLines', 'Question|text|required')
        ->call('createTemplate')
        ->assertForbidden();

    $templateB = app(CustomFormBuilderService::class)->createTemplate(User::factory()->admin(true)->create(), [
        'company_id' => $companyB->id,
        'title' => 'Company B Form',
        'category' => 'ops',
        'field_lines' => 'Question|text|required',
    ]);

    $this->actingAs($employeeA);

    app(CustomFormBuilderService::class)->submit($employeeA, $templateB, [
        'question' => 'Should fail',
    ]);
})->throws(HttpException::class);

test('custom form automation project selector follows selected company', function () {
    $superadmin = User::factory()->admin(true)->create();
    $companyA = app(MultiCompanyService::class)->createCompany('PT Form Scope A');
    $companyB = app(MultiCompanyService::class)->createCompany('PT Form Scope B');
    $projectA = Project::query()->create([
        'company_id' => $companyA->id,
        'name' => 'Form Project A',
        'status' => Project::STATUS_ACTIVE,
    ]);
    $projectB = Project::query()->create([
        'company_id' => $companyB->id,
        'name' => 'Form Project B',
        'status' => Project::STATUS_ACTIVE,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(CustomFormManager::class)
        ->set('templateCompanyId', (string) $companyA->id)
        ->set('automationEnabled', true)
        ->assertSee('Form Project A')
        ->assertDontSee('Form Project B');

    expect($projectA->exists)->toBeTrue()
        ->and($projectB->exists)->toBeTrue();
});

test('custom form manager keeps selected tab from query string on reload', function () {
    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin);

    Livewire::withQueryParams(['activeTab' => 'submissions'])
        ->test(CustomFormManager::class)
        ->assertSet('activeTab', 'submissions');

    Livewire::withQueryParams(['activeTab' => 'bad-tab'])
        ->test(CustomFormManager::class)
        ->assertSet('activeTab', 'templates');
});

test('custom form admin route requires explicit permission', function () {
    $admin = User::factory()->admin()->create();
    $admin->roles()->detach();

    $this->actingAs($admin)
        ->get(route('admin.custom-forms'))
        ->assertForbidden();

    $role = Role::query()->create([
        'name' => 'Form Viewer',
        'slug' => 'form_viewer',
        'permission_keys' => ['admin.custom_forms.view'],
    ]);
    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin->fresh())
        ->get(route('admin.custom-forms'))
        ->assertOk();
});
