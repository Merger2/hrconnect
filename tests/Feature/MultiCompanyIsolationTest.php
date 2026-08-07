<?php

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\CashAdvance;
use App\Models\CompanyAsset;
use App\Models\Employee;
use App\Models\EmployeeDocumentRequest;
use App\Models\EmployeeDocumentType;
use App\Models\HrChecklistCase;
use App\Models\HrChecklistTask;
use App\Models\HrChecklistTemplate;
use App\Models\HrChecklistTemplateItem;
use App\Models\ImportExportRun;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use App\Support\AdminDashboardQueryService;
use App\Support\HrChecklistService;
use App\Support\MultiCompanyService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

function tenantFixture(): array
{
    $tenants = app(MultiCompanyService::class);

    $adminA = User::factory()->admin()->create();
    $companyA = $tenants->createCompany('PT Isolation A', $adminA);
    $adminB = User::factory()->admin()->create();
    $companyB = $tenants->createCompany('PT Isolation B', $adminB);

    $employeeA = User::factory()->create(['company_id' => $companyA->id]);
    $employeeB = User::factory()->create(['company_id' => $companyB->id]);

    // managedBy() kini berbasis hierarki employee (parent_id), bukan company_id —
    // admin harus punya employee record dan bawahan di-link via parent_id.
    $adminAEmployee = Employee::factory()->create(['user_id' => $adminA->id, 'company_id' => $companyA->id]);
    $adminBEmployee = Employee::factory()->create(['user_id' => $adminB->id, 'company_id' => $companyB->id]);

    Employee::factory()->create([
        'user_id' => $employeeA->id,
        'company_id' => $companyA->id,
        'parent_id' => $adminAEmployee->id,
    ]);
    Employee::factory()->create([
        'user_id' => $employeeB->id,
        'company_id' => $companyB->id,
        'parent_id' => $adminBEmployee->id,
    ]);

    return compact('adminA', 'adminB', 'companyA', 'companyB', 'employeeA', 'employeeB');
}

test('company scoped user and attendance queries never include another tenant', function () {
    ['adminA' => $adminA, 'employeeA' => $employeeA, 'employeeB' => $employeeB] = tenantFixture();

    $attendanceA = Attendance::create([
        'employee_id' => $employeeA->employee->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);
    $attendanceB = Attendance::create([
        'employee_id' => $employeeB->employee->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);

    expect(User::query()->managedBy($adminA)->pluck('id')->all())
        ->toContain($employeeA->id)
        ->not->toContain($employeeB->id);

    expect(Attendance::query()->managedBy($adminA)->pluck('id')->all())
        ->toContain($attendanceA->id)
        ->not->toContain($attendanceB->id);
});

test('company scoped policies deny cross tenant sensitive HR finance and asset resources', function () {
    ['adminA' => $adminA, 'employeeA' => $employeeA, 'employeeB' => $employeeB] = tenantFixture();

    $attendanceB = Attendance::create([
        'employee_id' => $employeeB->employee->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);
    $payrollB = Payroll::create([
        'employee_id' => $employeeB->employee->id,
        'period' => now()->format('Y-m'),
        'basic_salary' => 1000000,
        'total_allowance' => 0,
        'gross_salary' => 1000000,
        'total_deduction' => 0,
        'overtime_pay' => 0,
        'net_salary' => 1000000,
        'status' => 'paid',
    ]);
    $reimbursementB = Reimbursement::create([
        'employee_id' => $employeeB->employee->id,
        'title' => 'Tenant B claim',
        'expense_date' => now()->toDateString(),
        'amount' => 100000,
        'description' => 'Tenant B claim',
        'status' => 'pending',
    ]);
    $cashAdvanceB = CashAdvance::create([
        'user_id' => $employeeB->id,
        'amount' => 100000,
        'purpose' => 'Tenant B advance',
        'status' => 'pending',
        'payment_month' => now()->month,
        'payment_year' => now()->year,
    ]);
    $assetB = CompanyAsset::create([
        'name' => 'Tenant B Laptop',
        'type' => 'Laptop',
        'user_id' => $employeeB->id,
        'status' => CompanyAsset::STATUS_ASSIGNED,
    ]);

    expect(Gate::forUser($adminA)->denies('view', $attendanceB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('download', $payrollB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('view', $reimbursementB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('approve', $reimbursementB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('view', $cashAdvanceB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('approve', $cashAdvanceB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('view', $assetB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('returnAsset', $assetB))->toBeTrue();

    $attendanceA = Attendance::create([
        'employee_id' => $employeeA->employee->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);

    expect(Gate::forUser($adminA)->allows('view', $attendanceA))->toBeTrue();
});

test('company scoped document HR checklist and import export downloads deny other tenants', function () {
    Storage::fake('local');

    ['adminA' => $adminA, 'adminB' => $adminB, 'employeeB' => $employeeB] = tenantFixture();

    $template = HrChecklistTemplate::create([
        'type' => HrChecklistTemplate::TYPE_ONBOARDING,
        'name' => 'Tenant Checklist',
        'is_active' => true,
        'created_by' => $adminB->id,
    ]);
    $template->items()->create([
        'title' => 'Tenant task',
        'category' => 'security',
        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_HR,
        'due_offset_days' => 0,
        'is_required' => true,
        'sort_order' => 1,
    ]);
    $caseB = app(HrChecklistService::class)->createCase($employeeB, $template->fresh('items'), $adminB, now());
    $taskB = $caseB->tasks()->firstOrFail();

    Storage::disk('local')->put('documents/tenant-b.pdf', 'tenant-b');
    $docType = EmployeeDocumentType::create([
        'name' => 'Surat Keterangan Kerja',
        'slug' => 'employment-certificate',
        'code' => 'employment_certificate',
        'is_active' => true,
    ]);
    $documentRequestB = EmployeeDocumentRequest::create([
        'employee_id' => $employeeB->employee->id,
        'document_type_id' => $docType->id,
        'requested_by' => $employeeB->id,
        'request_source' => 'employee',
        'purpose' => 'Tenant B',
        'status' => EmployeeDocumentRequest::STATUS_READY,
        'generated_path' => 'documents/tenant-b.pdf',
        'generated_at' => now(),
    ]);

    $role = Role::create([
        'name' => 'Tenant Export Admin_'.uniqid(),
        'slug' => 'tenant_export_admin_'.uniqid(),
        'description' => 'Can export tenant data.',
        'permission_keys' => ['admin.import_export_users.export'],
    ]);
    $adminA->roles()->syncWithoutDetaching([$role->id]);

    $runB = ImportExportRun::create([
        'resource' => 'users',
        'operation' => 'export',
        'status' => 'completed',
        'requested_by_user_id' => $adminB->id,
        'file_disk' => 'local',
        'file_path' => 'exports/tenant-b.xlsx',
        'file_name' => 'tenant-b.xlsx',
    ]);

    expect(Gate::forUser($adminA)->denies('view', $caseB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('view', $taskB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('download', $documentRequestB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('download', $runB))->toBeTrue();
});

test('tenant scoped activity log reporting can be constrained by actor company', function () {
    ['adminA' => $adminA, 'employeeA' => $employeeA, 'employeeB' => $employeeB] = tenantFixture();

    $logA = ActivityLog::create([
        'user_id' => $employeeA->id,
        'action' => 'Tenant A',
        'description' => 'Tenant A action',
    ]);
    $logB = ActivityLog::create([
        'user_id' => $employeeB->id,
        'action' => 'Tenant B',
        'description' => 'Tenant B action',
    ]);

    $visibleLogIds = ActivityLog::query()
        ->whereHas('user', fn ($query) => $query->managedBy($adminA))
        ->pluck('id')
        ->all();

    expect($visibleLogIds)
        ->toContain($logA->id)
        ->not->toContain($logB->id);
});

test('admin dashboard platform signals stay scoped to the current company', function () {
    ['adminA' => $adminA, 'adminB' => $adminB, 'companyA' => $companyA, 'companyB' => $companyB, 'employeeA' => $employeeA, 'employeeB' => $employeeB] = tenantFixture();

    $role = Role::create([
        'name' => 'Tenant Dashboard Operator_'.uniqid(),
        'slug' => 'tenant-dashboard-operator_'.uniqid(),
        'description' => 'Can view tenant dashboard signals.',
        'permission_keys' => [
            'admin.dashboard.view',
            'admin.wfh_requests.manage',
            'admin.hr_checklists.view',
            'admin.payroll.view',
            'admin.commercial.view',
            'admin.operations.view',
        ],
    ]);
    $adminA->roles()->syncWithoutDetaching([$role->id]);
    $adminA = $adminA->fresh('roles');

    foreach ([[$employeeA, $companyA], [$employeeB, $companyB]] as [$employee, $company]) {
        WorkFromHomeRequest::create([
            'user_id' => $employee->id,
            'company_id' => $company->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'location_address' => 'Home',
            'reason' => 'Tenant scoped WFH',
            'status' => WorkFromHomeRequest::STATUS_PENDING,
        ]);

        Payroll::create([
            'employee_id' => $employee->employee->id,
            'period' => now()->format('Y-m'),
            'basic_salary' => 1000000,
            'total_allowance' => 0,
            'gross_salary' => 1000000,
            'total_deduction' => 0,
            'overtime_pay' => 0,
            'net_salary' => 1000000,
            'status' => 'submitted',
        ]);

        $riskAttendance = Attendance::create([
            'employee_id' => $employee->employee->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'approval_status' => Attendance::STATUS_APPROVED,
        ]);
        // risk_level/risk_score dihitung sistem (bukan fillable) — set langsung
        // agar sinyal high_risk_attendance terisi.
        $riskAttendance->forceFill(['risk_level' => 'high', 'risk_score' => 80])->save();

        $project = Project::create([
            'company_id' => $company->id,
            'manager_id' => $employee->id,
            'name' => 'Project '.$company->id,
            'status' => Project::STATUS_ACTIVE,
        ]);

        ProjectTask::create([
            'project_id' => $project->id,
            'company_id' => $company->id,
            'assigned_to' => $employee->id,
            'title' => 'Overdue task '.$company->id,
            'status' => ProjectTask::STATUS_TODO,
            'priority' => ProjectTask::PRIORITY_NORMAL,
            'due_date' => now()->subDay()->toDateString(),
        ]);
    }

    foreach ([[$adminA, $employeeA], [$adminB, $employeeB]] as [$starter, $employee]) {
        $template = HrChecklistTemplate::create([
            'type' => HrChecklistTemplate::TYPE_ONBOARDING,
            'name' => 'Dashboard Checklist '.$employee->id,
            'is_active' => true,
            'created_by' => $starter->id,
        ]);

        $case = HrChecklistCase::create([
            'template_id' => $template->id,
            'user_id' => $employee->id,
            'type' => HrChecklistTemplate::TYPE_ONBOARDING,
            'status' => HrChecklistCase::STATUS_ACTIVE,
            'effective_date' => now()->toDateString(),
            'started_by' => $starter->id,
        ]);

        $case->tasks()->create([
            'title' => 'Overdue tenant task',
            'category' => 'onboarding',
            'assigned_to' => $starter->id,
            'due_date' => now()->subDay()->toDateString(),
            'status' => HrChecklistTask::STATUS_PENDING,
        ]);
    }

    $dashboard = app(AdminDashboardQueryService::class)->build($adminA, now());

    expect($dashboard['platformSignals'])->toMatchArray([
        'pending_wfh' => 1,
        'overdue_hr_tasks' => 1,
        'high_risk_attendance' => 1,
        'pending_payroll' => 1,
        'overdue_project_tasks' => 1,
    ]);
});
