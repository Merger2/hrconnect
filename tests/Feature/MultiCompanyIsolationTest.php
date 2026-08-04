<?php

use App\Exports\LeaveRequestsExport;
use App\Exports\PayrollSummaryExport;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\CashAdvance;
use App\Models\CompanyAsset;
use App\Models\Employee;
use App\Models\EmployeeDocumentRequest;
use App\Models\HrChecklistCase;
use App\Models\HrChecklistTask;
use App\Models\HrChecklistTemplate;
use App\Models\HrChecklistTemplateItem;
use App\Models\ImportExportRun;
use App\Models\Invoice;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\SalesOpportunity;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBill;
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

    Employee::factory()->create(['user_id' => $employeeA->id, 'company_id' => $companyA->id]);
    Employee::factory()->create(['user_id' => $employeeB->id, 'company_id' => $companyB->id]);

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
        'allowances' => [],
        'deductions' => [],
        'overtime_pay' => 0,
        'net_salary' => 1000000,
        'status' => 'paid',
    ]);
    $reimbursementB = Reimbursement::create([
        'employee_id' => $employeeB->employee->id,
        'date' => now()->toDateString(),
        'type' => 'medical',
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
    $documentRequestB = EmployeeDocumentRequest::create([
        'employee_id' => $employeeB->employee->id,
        'document_type' => EmployeeDocumentRequest::TYPE_EMPLOYMENT_CERTIFICATE,
        'requested_by' => $employeeB->id,
        'request_source' => EmployeeDocumentRequest::SOURCE_EMPLOYEE,
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

test('company scoped report exports exclude other tenant rows', function () {
    ['adminA' => $adminA, 'employeeA' => $employeeA, 'employeeB' => $employeeB] = tenantFixture();

    $leaveA = Attendance::create([
        'employee_id' => $employeeA->employee->id,
        'date' => now()->toDateString(),
        'status' => 'sick',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);
    $leaveB = Attendance::create([
        'employee_id' => $employeeB->employee->id,
        'date' => now()->toDateString(),
        'status' => 'sick',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);
    $payrollA = Payroll::create([
        'employee_id' => $employeeA->employee->id,
        'period' => now()->format('Y-m'),
        'basic_salary' => 1000000,
        'allowances' => [],
        'deductions' => [],
        'overtime_pay' => 0,
        'net_salary' => 1000000,
        'status' => 'paid',
    ]);
    $payrollB = Payroll::create([
        'employee_id' => $employeeB->employee->id,
        'period' => now()->format('Y-m'),
        'basic_salary' => 1000000,
        'allowances' => [],
        'deductions' => [],
        'overtime_pay' => 0,
        'net_salary' => 1000000,
        'status' => 'paid',
    ]);

    expect((new LeaveRequestsExport($adminA))->query()->pluck('id')->all())
        ->toContain($leaveA->id)
        ->not->toContain($leaveB->id);

    expect((new PayrollSummaryExport($adminA))->query()->pluck('id')->all())
        ->toContain($payrollA->id)
        ->not->toContain($payrollB->id);
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
            'allowances' => [],
            'deductions' => [],
            'overtime_pay' => 0,
            'net_salary' => 1000000,
            'status' => 'pending',
        ]);

        Attendance::create([
            'employee_id' => $employee->employee->id,
            'date' => now()->toDateString(),
            'status' => 'present',
            'approval_status' => Attendance::STATUS_APPROVED,
            'risk_level' => 'high',
            'risk_score' => 80,
        ]);

        Invoice::create([
            'company_id' => $company->id,
            'number' => 'INV-'.$company->id,
            'status' => Invoice::STATUS_SENT,
            'grand_total' => 1000000,
        ]);

        $vendor = Vendor::create([
            'company_id' => $company->id,
            'name' => 'Vendor '.$company->id,
        ]);

        VendorBill::create([
            'company_id' => $company->id,
            'vendor_id' => $vendor->id,
            'number' => 'BILL-'.$company->id,
            'status' => VendorBill::STATUS_POSTED,
            'grand_total' => 500000,
        ]);

        SalesOpportunity::create([
            'company_id' => $company->id,
            'owner_id' => $employee->id,
            'title' => 'Opportunity '.$company->id,
            'stage' => SalesOpportunity::STAGE_PROPOSAL,
            'expected_value' => 2000000,
        ]);

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
        'open_invoices' => 1,
        'open_vendor_bills' => 1,
        'active_sales_opportunities' => 1,
        'overdue_project_tasks' => 1,
    ]);
});
