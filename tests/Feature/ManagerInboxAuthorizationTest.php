<?php

use App\Livewire\Admin\ManagerInbox;
use App\Models\Attendance;
use App\Models\CashAdvance;
use App\Models\CustomFormSubmission;
use App\Models\CustomFormTemplate;
use App\Models\HrChecklistCase;
use App\Models\HrChecklistTask;
use App\Models\HrChecklistTemplate;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use App\Support\ManagerInboxService;
use App\Support\MultiCompanyService;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
});

test('manager inbox only exposes tabs allowed by admin rbac permissions', function () {
    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Leave Inbox Reviewer_'.uniqid(),
        'slug' => 'leave_inbox_reviewer_'.uniqid(),
        'description' => 'Can review leave requests from the manager inbox only.',
        'permission_keys' => [
            'admin.dashboard.view',
            'admin.leave_approvals.approve',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    Livewire::actingAs($admin->fresh())
        ->test(ManagerInbox::class)
        ->assertSee(__('Leaves'))
        ->assertDontSee(__('Cash Advances'))
        ->assertDontSee(__('Reimbursements'));

    expect(app(ManagerInboxService::class)->accessibleTabs($admin->fresh()))->toBe(['leaves']);
});

test('manager inbox is forbidden when admin has no reviewable modules', function () {
    $admin = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'Dashboard Only Inbox Regression_'.uniqid(),
        'slug' => 'dashboard_only_inbox_regression_'.uniqid(),
        'description' => 'Can access the admin dashboard only.',
        'permission_keys' => ['admin.dashboard.view'],
    ]);

    $admin->roles()->sync([$role->id]);

    $this->actingAs($admin->fresh())
        ->get(route('admin.inbox'))
        ->assertForbidden();
});

test('manager inbox rejects crafted tab changes outside admin rbac permissions', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $role = Role::create([
        'name' => 'Leave Only Crafted Inbox Regression_'.uniqid(),
        'slug' => 'leave_only_crafted_inbox_regression_'.uniqid(),
        'description' => 'Can review leaves, but not cash advances.',
        'permission_keys' => [
            'admin.dashboard.view',
            'admin.leave_approvals.approve',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    $advance = CashAdvance::create([
        'user_id' => $employee->id,
        'amount' => 200000,
        'purpose' => 'Travel advance',
        'payment_month' => (int) now()->month,
        'payment_year' => (int) now()->year,
        'status' => 'pending',
    ]);

    Livewire::actingAs($admin->fresh())
        ->test(ManagerInbox::class)
        ->set('activeTab', 'cash_advances')
        ->assertForbidden();

    expect($advance->fresh()->status)->toBe('pending');
});

test('manager inbox summarizes and filters overdue approvals', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $leaveType = LeaveType::create([
        'code' => 'special_approval_test',
        'name' => 'Special Approval Test',
        'is_active' => true,
    ]);
    $role = Role::create([
        'name' => 'Leave Overdue Inbox Reviewer_'.uniqid(),
        'slug' => 'leave_overdue_inbox_reviewer_'.uniqid(),
        'description' => 'Can review overdue leave requests from the manager inbox.',
        'permission_keys' => [
            'admin.dashboard.view',
            'admin.leave_approvals.approve',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    $attendance = Attendance::create([
        'user_id' => $employee->id,
        'date' => now()->toDateString(),
        'status' => 'excused',
        'approval_status' => 'pending',
        'leave_type_id' => $leaveType->id,
    ]);
    $attendance->forceFill([
        'created_at' => now()->subDays(3),
        'updated_at' => now()->subDays(3),
    ])->save();

    Livewire::actingAs($admin->fresh())
        ->test(ManagerInbox::class)
        ->assertSee(__('Overdue'))
        ->assertSet('statusFilter', 'pending')
        ->call('setStatusFilter', 'overdue')
        ->assertSet('statusFilter', 'overdue')
        ->assertSee($employee->name);
});

test('manager inbox includes hr checklist blockers and quick actions', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $role = Role::create([
        'name' => 'HR Task Inbox Reviewer_'.uniqid(),
        'slug' => 'hr_task_inbox_reviewer_'.uniqid(),
        'description' => 'Can review HR checklist tasks from the manager inbox.',
        'permission_keys' => [
            'admin.dashboard.view',
            'admin.hr_checklists.view',
            'admin.hr_checklists.manage',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    $template = HrChecklistTemplate::create([
        'type' => HrChecklistTemplate::TYPE_ONBOARDING,
        'name' => 'Inbox HR Task Template',
        'is_active' => true,
        'created_by' => $admin->id,
    ]);

    $case = HrChecklistCase::create([
        'template_id' => $template->id,
        'user_id' => $employee->id,
        'type' => HrChecklistTemplate::TYPE_ONBOARDING,
        'status' => HrChecklistCase::STATUS_ACTIVE,
        'effective_date' => now()->toDateString(),
        'started_by' => $admin->id,
    ]);

    $task = HrChecklistTask::create([
        'case_id' => $case->id,
        'title' => 'Collect laptop return form',
        'status' => HrChecklistTask::STATUS_PENDING,
        'due_date' => now()->subDay()->toDateString(),
    ]);

    Livewire::actingAs($admin->fresh())
        ->test(ManagerInbox::class)
        ->assertSee(__('HR Tasks'))
        ->call('switchTab', 'hr_tasks')
        ->assertSee('Collect laptop return form')
        ->assertSee(__('Mark Done'))
        ->call('confirmReject', $task->id)
        ->set('rejectionReason', 'Waiting for asset officer')
        ->call('reject')
        ->assertSee(__('Blocked'));

    expect($task->fresh()->status)->toBe(HrChecklistTask::STATUS_BLOCKED);

    Livewire::actingAs($admin->fresh())
        ->test(ManagerInbox::class, ['activeTab' => 'hr_tasks'])
        ->call('setStatusFilter', 'blocked')
        ->assertSee('Collect laptop return form')
        ->call('approve', $task->id);

    expect($task->fresh()->status)->toBe(HrChecklistTask::STATUS_DONE);
});

test('manager inbox can approve work from home requests', function () {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $role = Role::create([
        'name' => 'WFH Inbox Reviewer_'.uniqid(),
        'slug' => 'wfh_inbox_reviewer_'.uniqid(),
        'description' => 'Can review WFH requests from the manager inbox.',
        'permission_keys' => [
            'admin.dashboard.view',
            'admin.wfh_requests.manage',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    $request = WorkFromHomeRequest::create([
        'user_id' => $employee->id,
        'company_id' => $employee->company_id,
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'date' => now()->addDay()->toDateString(),
        'reason' => 'Remote client support',
        'status' => WorkFromHomeRequest::STATUS_PENDING,
    ]);

    Livewire::actingAs($admin->fresh())
        ->test(ManagerInbox::class)
        ->assertSee(__('WFH'))
        ->call('switchTab', 'wfh_requests')
        ->assertSee('Remote client support')
        ->call('approve', $request->id);

    expect($request->fresh()->status)->toBe(WorkFromHomeRequest::STATUS_APPROVED);
});

test('manager inbox can mark custom form submissions reviewed within company scope', function () {
    $companyA = app(MultiCompanyService::class)->createCompany('PT Inbox Forms A');
    $companyB = app(MultiCompanyService::class)->createCompany('PT Inbox Forms B');
    $admin = User::factory()->admin()->create(['company_id' => $companyA->id]);
    $employeeA = User::factory()->create(['company_id' => $companyA->id]);
    $employeeB = User::factory()->create(['company_id' => $companyB->id]);
    $role = Role::create([
        'name' => 'Forms Inbox Reviewer_'.uniqid(),
        'slug' => 'forms_inbox_reviewer_'.uniqid(),
        'description' => 'Can review custom form submissions from the manager inbox.',
        'permission_keys' => [
            'admin.dashboard.view',
            'admin.custom_forms.view',
        ],
    ]);

    $admin->roles()->sync([$role->id]);

    $templateA = CustomFormTemplate::create([
        'company_id' => $companyA->id,
        'title' => 'Visit Report A',
        'category' => 'operations',
        'fields' => [['key' => 'site', 'label' => 'Site', 'type' => 'text', 'required' => true, 'options' => []]],
        'is_active' => true,
    ]);
    $templateB = CustomFormTemplate::create([
        'company_id' => $companyB->id,
        'title' => 'Visit Report B',
        'category' => 'operations',
        'fields' => [['key' => 'site', 'label' => 'Site', 'type' => 'text', 'required' => true, 'options' => []]],
        'is_active' => true,
    ]);

    $submissionA = CustomFormSubmission::create([
        'custom_form_template_id' => $templateA->id,
        'company_id' => $companyA->id,
        'submitted_by' => $employeeA->id,
        'status' => CustomFormSubmission::STATUS_SUBMITTED,
        'payload' => ['site' => 'Bandung'],
    ]);
    $submissionB = CustomFormSubmission::create([
        'custom_form_template_id' => $templateB->id,
        'company_id' => $companyB->id,
        'submitted_by' => $employeeB->id,
        'status' => CustomFormSubmission::STATUS_SUBMITTED,
        'payload' => ['site' => 'Jakarta'],
    ]);

    Livewire::actingAs($admin->fresh())
        ->test(ManagerInbox::class)
        ->assertSee(__('Forms'))
        ->call('switchTab', 'custom_forms')
        ->assertSee('Visit Report A')
        ->assertDontSee('Visit Report B')
        ->call('approve', $submissionA->id);

    expect($submissionA->fresh()->status)->toBe(CustomFormSubmission::STATUS_REVIEWED)
        ->and($submissionA->fresh()->metadata['reviewed_by'])->toBe($admin->id)
        ->and($submissionB->fresh()->status)->toBe(CustomFormSubmission::STATUS_SUBMITTED);
});
