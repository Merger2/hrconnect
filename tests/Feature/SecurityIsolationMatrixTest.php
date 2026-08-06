<?php

use App\Models\Attendance;
use App\Models\CashAdvance;
use App\Models\CompanyAsset;
use App\Models\Employee;
use App\Models\HrChecklistTemplate;
use App\Models\HrChecklistTemplateItem;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\User;
use App\Support\HrChecklistService;
use App\Support\MultiCompanyService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

test('sensitive resource policies deny cross company access for tenant scoped admins', function () {
    $tenant = app(MultiCompanyService::class);
    $adminA = User::factory()->admin()->create();
    $companyA = $tenant->createCompany('PT Tenant A', $adminA);
    $companyB = $tenant->createCompany('PT Tenant B');
    $employeeA = User::factory()->create(['company_id' => $companyA->id]);
    $employeeBUser = User::factory()->create(['company_id' => $companyB->id]);
    // employees.user_id UNIQUE — satu record employee per user, dipakai ulang.
    $employeeB = Employee::factory()->create(['user_id' => $employeeBUser->id]);

    $attendanceB = Attendance::create([
        'employee_id' => $employeeB->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);
    $payrollB = Payroll::create([
        'employee_id' => $employeeB->id,
        'period' => now()->format('Y-m'),
        'basic_salary' => 1000000,
        'total_allowance' => 0,
        'gross_salary' => 1000000,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 1000000,
        'status' => 'paid',
    ]);
    $reimbursementB = Reimbursement::create([
        'employee_id' => $employeeB->id,
        'title' => 'Tenant B claim',
        'expense_date' => now()->toDateString(),
        'amount' => 100000,
        'description' => 'Tenant B claim',
        'status' => 'pending',
    ]);
    $cashAdvanceB = CashAdvance::create([
        'user_id' => $employeeBUser->id,
        'amount' => 100000,
        'purpose' => 'Tenant B advance',
        'status' => 'pending',
        'payment_month' => now()->month,
        'payment_year' => now()->year,
    ]);
    $assetB = CompanyAsset::create([
        'name' => 'Tenant B Laptop',
        'type' => 'Laptop',
        'user_id' => $employeeBUser->id,
        'status' => CompanyAsset::STATUS_ASSIGNED,
    ]);

    $template = HrChecklistTemplate::create([
        'type' => HrChecklistTemplate::TYPE_ONBOARDING,
        'name' => 'Tenant Isolation Checklist',
        'is_active' => true,
        'created_by' => $adminA->id,
    ]);
    $template->items()->create([
        'title' => 'Tenant task',
        'category' => 'security',
        'default_assignee_type' => HrChecklistTemplateItem::ASSIGNEE_HR,
        'due_offset_days' => 0,
        'is_required' => true,
        'sort_order' => 1,
    ]);
    $caseB = app(HrChecklistService::class)->createCase($employeeBUser, $template->fresh('items'), $adminA, now());
    $taskB = $caseB->tasks()->firstOrFail();

    expect(Gate::forUser($adminA)->denies('view', $attendanceB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('download', $payrollB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('view', $reimbursementB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('view', $cashAdvanceB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('view', $assetB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('view', $caseB))->toBeTrue()
        ->and(Gate::forUser($adminA)->denies('view', $taskB))->toBeTrue();

    $attendanceA = Attendance::create([
        'employee_id' => Employee::factory()->create(['user_id' => $employeeA->id])->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);

    expect(Gate::forUser($adminA)->allows('view', $attendanceA))->toBeTrue();
});

test('attachment download route denies cross company reimbursement access', function () {
    Storage::fake('local');

    $tenant = app(MultiCompanyService::class);
    $adminA = User::factory()->admin()->create();
    $companyA = $tenant->createCompany('PT Attachment A', $adminA);
    $companyB = $tenant->createCompany('PT Attachment B');
    User::factory()->create(['company_id' => $companyA->id]);
    $employeeB = User::factory()->create(['company_id' => $companyB->id]);

    Storage::disk('local')->put('reimbursements/tenant-b.pdf', 'tenant-b');
    $reimbursement = Reimbursement::create([
        'employee_id' => Employee::factory()->create(['user_id' => $employeeB->id])->id,
        'title' => 'Tenant B claim',
        'expense_date' => now()->toDateString(),
        'amount' => 100000,
        'description' => 'Tenant B claim',
        'attachment_path' => 'reimbursements/tenant-b.pdf',
        'status' => 'pending',
    ]);

    $this->actingAs($adminA)
        ->get(route('reimbursement.attachment.download', $reimbursement))
        ->assertForbidden();
});
