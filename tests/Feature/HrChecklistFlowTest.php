<?php

use App\Actions\Hr\CreateChecklistCaseForEmployeeStatus;
use App\Livewire\Admin\HrChecklistManager;
use App\Livewire\User\HrTasksPage;
use App\Models\Division;
use App\Models\Employee;
use App\Models\HrChecklistCase;
use App\Models\HrChecklistTask;
use App\Models\HrChecklistTemplate;
use App\Models\HrChecklistTemplateItem;
use App\Models\JobTitle;
use App\Models\Role;
use App\Models\User;
use App\Support\HrChecklistService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('admin and hr roles can access hr checklists while employees cannot', function () {
    $admin = User::factory()->admin()->create();
    $hr = User::factory()->admin()->create();
    $employee = User::factory()->create();

    // Role 'hr' tidak lagi di-seed (hr-manager dihapus; HRD memakai role
    // admin) — buat role self-contained dengan permission HR checklist
    // eksplisit supaya test tidak bergantung pada seeder.
    $adminRole = Role::create([
        'name' => 'HR Checklist Admin_'.uniqid(),
        'slug' => 'hr_checklist_admin_'.uniqid(),
        'permission_keys' => ['admin.hr_checklists.view', 'admin.hr_checklists.manage'],
    ]);
    $hrRole = Role::create([
        'name' => 'HR Checklist HR_'.uniqid(),
        'slug' => 'hr_checklist_hr_'.uniqid(),
        'permission_keys' => ['admin.hr_checklists.view', 'admin.hr_checklists.manage'],
    ]);
    $admin->roles()->sync([$adminRole->id]);
    $hr->roles()->sync([$hrRole->id]);

    expect(Gate::forUser($admin)->allows('viewHrChecklists'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageHrChecklists'))->toBeTrue()
        ->and(Gate::forUser($hr)->allows('viewHrChecklists'))->toBeTrue()
        ->and(Gate::forUser($hr)->allows('manageHrChecklists'))->toBeTrue()
        ->and(Gate::forUser($employee)->allows('viewHrChecklists'))->toBeFalse();

    $this->actingAs($employee)
        ->get(route('admin.hr-checklists'))
        ->assertForbidden();
});

test('hr can start onboarding checklist case with employee manager and hr tasks', function () {

    $hr = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $manager = User::factory()->create();
    $employee->update(['manager_id' => $manager->id]);

    // startCase() mewajibkan templateId — pakai template onboarding default
    // dari service (sama dengan test 'assigned employee can complete').
    $service = app(HrChecklistService::class);
    $service->ensureDefaultTemplates();
    $template = HrChecklistTemplate::query()
        ->where('type', HrChecklistTemplate::TYPE_ONBOARDING)
        ->firstOrFail();

    $this->actingAs($hr);

    Livewire::test(HrChecklistManager::class)
        ->call('createCase')
        ->set('employeeId', $employee->id)
        ->set('type', HrChecklistTemplate::TYPE_ONBOARDING)
        ->set('effectiveDate', '2026-05-10')
        ->set('templateId', $template->id)
        ->call('startCase')
        ->assertHasNoErrors();

    $case = HrChecklistCase::query()
        ->with('tasks')
        ->where('user_id', $employee->id)
        ->where('type', HrChecklistTemplate::TYPE_ONBOARDING)
        ->firstOrFail();

    expect($case->status)->toBe(HrChecklistCase::STATUS_ACTIVE)
        ->and($case->tasks)->toHaveCount(4)
        ->and($case->tasks->pluck('assigned_to')->all())->toContain($employee->id, $manager->id, $hr->id);
});

test('assigned employee can complete only their hr checklist task', function () {
    $service = app(HrChecklistService::class);
    $service->ensureDefaultTemplates();

    $hr = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $manager = User::factory()->create();
    $otherEmployee = User::factory()->create();
    $employee->update(['manager_id' => $manager->id]);

    $template = HrChecklistTemplate::query()
        ->where('type', HrChecklistTemplate::TYPE_ONBOARDING)
        ->with('items')
        ->firstOrFail();

    $case = $service->createCase($employee, $template, $hr, '2026-05-10');
    $employeeTask = $case->tasks()
        ->where('assigned_to', $employee->id)
        ->firstOrFail();
    $managerTask = $case->tasks()
        ->where('assigned_to', $manager->id)
        ->firstOrFail();

    $this->actingAs($employee);

    Livewire::test(HrTasksPage::class)
        ->assertSee(__($employeeTask->title))
        ->assertDontSee(__($managerTask->title))
        ->set("taskNotes.{$employeeTask->id}", 'Submitted from mobile.')
        ->call('updateTask', $employeeTask->id, HrChecklistTask::STATUS_DONE)
        ->assertHasNoErrors();

    expect($employeeTask->refresh()->status)->toBe(HrChecklistTask::STATUS_DONE)
        ->and($employeeTask->completed_by)->toBe($employee->id)
        ->and($employeeTask->notes)->toBe('Submitted from mobile.');

    $this->actingAs($otherEmployee);

    Livewire::test(HrTasksPage::class)
        ->call('updateTask', $managerTask->id, HrChecklistTask::STATUS_DONE)
        ->assertForbidden();
});

test('checklist case is completed when all tasks are closed', function () {
    $service = app(HrChecklistService::class);
    $service->ensureDefaultTemplates();

    $hr = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $template = HrChecklistTemplate::create([
        'type' => HrChecklistTemplate::TYPE_OFFBOARDING,
        'name' => 'One Task Offboarding',
        'description' => 'Single task template.',
        'is_active' => true,
        'created_by' => $hr->id,
    ]);
    $template->items()->create([
        'title' => 'Confirm final note',
        'category' => 'general',
        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_EMPLOYEE,
        'due_offset_days' => 0,
        'is_required' => true,
        'sort_order' => 1,
    ]);

    $case = $service->createCase($employee, $template->fresh('items'), $hr, now());
    $task = $case->tasks()->firstOrFail();

    $service->updateTaskStatus($task, $employee, HrChecklistTask::STATUS_DONE, null);

    expect($case->refresh()->status)->toBe(HrChecklistCase::STATUS_COMPLETED)
        ->and($case->completed_at)->not->toBeNull();
});

test('hr checklist v2 exposes overdue reminder dependency attachment and clearance summary', function () {
    $service = app(HrChecklistService::class);
    $hr = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $template = HrChecklistTemplate::create([
        'type' => HrChecklistTemplate::TYPE_ONBOARDING,
        'name' => 'Dependency Onboarding',
        'is_active' => true,
        'created_by' => $hr->id,
    ]);
    $firstItem = $template->items()->create([
        'title' => 'Upload signed policy',
        'category' => 'documents',
        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_EMPLOYEE,
        'due_offset_days' => -2,
        'is_required' => true,
        'sort_order' => 1,
    ]);
    $template->items()->create([
        'title' => 'Confirm system access',
        'category' => 'access',
        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_HR,
        'due_offset_days' => -1,
        'is_required' => true,
        'sort_order' => 2,
        'metadata' => ['depends_on_previous' => true],
    ]);

    $case = $service->createCase($employee, $template->fresh('items'), $hr, now()->subDay());
    $firstTask = $case->tasks()->where('template_item_id', $firstItem->id)->firstOrFail();
    $dependentTask = $case->tasks()->where('id', '!=', $firstTask->id)->firstOrFail();

    expect(HrChecklistTask::query()->reminderReady()->pluck('id')->all())->toContain($firstTask->id)
        ->and($dependentTask->depends_on_task_id)->toBe($firstTask->id)
        ->and($case->fresh('tasks')->clearanceSummary()['overdue'])->toBe(2);

    expect(fn () => $service->updateTaskStatus($dependentTask, $hr, HrChecklistTask::STATUS_DONE))
        ->toThrow(ValidationException::class);

    $service->recordTaskAttachment($firstTask, 'hr-checklists/policy.pdf', 'policy.pdf');
    $service->updateTaskStatus($firstTask->fresh(), $employee, HrChecklistTask::STATUS_DONE);
    $service->updateTaskStatus($dependentTask->fresh(), $hr, HrChecklistTask::STATUS_DONE);

    $summary = $case->fresh('tasks')->clearanceSummary();

    expect($firstTask->fresh()->attachment_original_name)->toBe('policy.pdf')
        ->and($case->refresh()->status)->toBe(HrChecklistCase::STATUS_COMPLETED)
        ->and($summary['clearance_ready'])->toBeTrue();
});

test('hr checklist task attachment download is protected by task policy', function () {
    Storage::fake('local');

    $service = app(HrChecklistService::class);
    $hr = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $otherEmployee = User::factory()->create();
    $template = HrChecklistTemplate::create([
        'type' => HrChecklistTemplate::TYPE_ONBOARDING,
        'name' => 'Attachment Checklist',
        'is_active' => true,
        'created_by' => $hr->id,
    ]);
    $template->items()->create([
        'title' => 'Review attachment',
        'category' => 'documents',
        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_EMPLOYEE,
        'due_offset_days' => 0,
        'is_required' => true,
        'sort_order' => 1,
    ]);

    $case = $service->createCase($employee, $template->fresh('items'), $hr, now());
    $task = $case->tasks()->firstOrFail();
    Storage::disk('local')->put('hr-checklists/policy.pdf', 'signed-policy');
    $service->recordTaskAttachment($task, 'hr-checklists/policy.pdf', 'policy.pdf');

    $this->actingAs($otherEmployee)
        ->get(route('hr-checklist.task-attachment.download', $task))
        ->assertForbidden();

    $this->actingAs($employee)
        ->get(route('hr-checklist.task-attachment.download', $task))
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('hr checklist can be auto-created from employee status using scoped template', function () {
    $service = app(HrChecklistService::class);
    $hr = User::factory()->admin()->create();
    $division = Division::create(['name' => 'Scoped HR', 'code' => 'SHR_'.uniqid()]);
    $jobTitle = JobTitle::create(['name' => 'Scoped Staff', 'division_id' => $division->id]);
    $employee = User::factory()->create();
    // employees tidak punya kolom job_title_id (pakai position_id) — scoping
    // template by division yang dipakai.
    Employee::factory()->create([
        'user_id' => $employee->id,
        'division_id' => $division->id,
    ]);

    $template = HrChecklistTemplate::create([
        'type' => HrChecklistTemplate::TYPE_OFFBOARDING,
        'name' => 'Scoped Offboarding',
        'division_id' => $division->id,
        'job_title_id' => $jobTitle->id,
        'is_active' => true,
        'created_by' => $hr->id,
    ]);
    $template->items()->create([
        'title' => 'Return division asset',
        'category' => 'assets',
        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_HR,
        'due_offset_days' => 0,
        'is_required' => true,
        'sort_order' => 1,
    ]);

    $case = app(CreateChecklistCaseForEmployeeStatus::class)
        ->handle($employee, $hr, User::EMPLOYMENT_STATUS_RESIGNED, now());

    expect($case)->not->toBeNull()
        ->and($case->template_id)->toBe($template->id)
        ->and($case->tasks)->toHaveCount(1);
});
