<?php

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Enums\PayrollStatus;
use App\Enums\ReimbursementStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Approval;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\Reimbursement;
use App\Services\ApprovalService;
use App\Services\ReimbursementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $department = Department::factory()->for($branch)->create();
    $position = Position::factory()->for($department)->create();

    $this->employee = Employee::factory()->create([
        'position_id' => $position->id,
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'parent_id' => null,
    ]);

    $this->approver = Employee::factory()->create([
        'position_id' => $position->id,
        'company_id' => $company->id,
        'branch_id' => $branch->id,
        'department_id' => $department->id,
    ]);
});

// ─── createReimbursement() ──────────────────────────────────────────────

test('creates reimbursement without attachment', function () {
    $approvalService = $this->createMock(ApprovalService::class);
    $service = new ReimbursementService($approvalService);

    $data = [
        'title' => 'Biaya Transportasi',
        'expense_date' => '2026-06-05',
        'amount' => 150_000,
        'description' => 'Perjalanan dinas ke client',
    ];

    $result = $service->createReimbursement($this->employee, $data);

    expect($result)
        ->title->toBe('Biaya Transportasi')
        ->description->toBe('Perjalanan dinas ke client')
        ->receipt_file->toBeNull()
        ->status->toBe(ReimbursementStatus::PENDING)
        ->employee_id->toBe($this->employee->id);
    expect((float) $result->amount)->toBe(150_000.0);
    expect($result->expense_date->toDateString())->toBe('2026-06-05');
});

test('creates reimbursement with attachment', function () {
    Storage::fake('local');

    $approvalService = $this->createMock(ApprovalService::class);
    $service = new ReimbursementService($approvalService);

    $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');
    $data = [
        'title' => 'Biaya Konsumsi',
        'expense_date' => '2026-06-10',
        'amount' => 250_000,
        'receipt' => $file,
    ];

    $result = $service->createReimbursement($this->employee, $data);

    expect($result->receipt_file)->not->toBeNull();
    Storage::disk('local')->assertExists($result->receipt_file);
});

test('creates approval workflow via ApprovalService', function () {
    $approvalService = $this->createMock(ApprovalService::class);
    $approvalService->expects($this->once())
        ->method('createApprovalWorkflow')
        ->with($this->isInstanceOf(Reimbursement::class));

    $service = new ReimbursementService($approvalService);

    $data = [
        'title' => 'Biaya Meeting',
        'expense_date' => '2026-06-12',
        'amount' => 500_000,
    ];

    $service->createReimbursement($this->employee, $data);
});

// ─── approve() ──────────────────────────────────────────────────────────

test('approves via ApprovalService when pending approval exists', function () {
    $reimbursement = Reimbursement::factory()->create([
        'employee_id' => $this->employee->id,
        'status' => ReimbursementStatus::PENDING,
    ]);
    $approval = $reimbursement->approvals()->create([
        'approver_id' => $this->approver->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    $approvalService = $this->createMock(ApprovalService::class);
    $approvalService->expects($this->once())
        ->method('approve')
        ->with(
            $this->callback(fn (Approval $a) => $a->id === $approval->id),
            'Setuju',
        );

    $service = new ReimbursementService($approvalService);
    $service->approve($reimbursement, $this->approver, 'Setuju');
});

test('approve throws BusinessRuleException when no pending approval', function () {
    $reimbursement = Reimbursement::factory()->create([
        'employee_id' => $this->employee->id,
        'status' => ReimbursementStatus::PENDING,
    ]);

    $service = new ReimbursementService($this->createMock(ApprovalService::class));
    $service->approve($reimbursement, $this->approver);
})->throws(BusinessRuleException::class, 'Tiket persetujuan tidak ditemukan');

// ─── reject() ───────────────────────────────────────────────────────────

test('rejects via ApprovalService when pending approval exists', function () {
    $reimbursement = Reimbursement::factory()->create([
        'employee_id' => $this->employee->id,
        'status' => ReimbursementStatus::PENDING,
    ]);
    $reimbursement->approvals()->create([
        'approver_id' => $this->approver->id,
        'level' => ApprovalLevel::L2_MANAGER,
        'status' => ApprovalStatus::PENDING,
    ]);

    $approvalService = $this->createMock(ApprovalService::class);
    $approvalService->expects($this->once())
        ->method('reject')
        ->with(
            $this->callback(fn (Approval $a) => $a->approver_id === $this->approver->id),
            'Nota tidak valid',
        );

    $service = new ReimbursementService($approvalService);
    $service->reject($reimbursement, $this->approver, 'Nota tidak valid');
});

// ─── linkToPayroll() ────────────────────────────────────────────────────

test('links approved reimbursement to unlocked payroll', function () {
    $reimbursement = Reimbursement::factory()->create([
        'employee_id' => $this->employee->id,
        'status' => ReimbursementStatus::APPROVED,
        'payroll_id' => null,
    ]);
    $payroll = Payroll::create([
        'employee_id' => $this->employee->id,
        'period' => '2026-06',
        'basic_salary' => 0,
        'total_allowance' => 0,
        'gross_salary' => 0,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 0,
        'status' => PayrollStatus::DRAFT,
    ]);

    $service = new ReimbursementService($this->createMock(ApprovalService::class));
    $service->linkToPayroll($reimbursement, $payroll->id);

    $reimbursement->refresh();
    expect($reimbursement->payroll_id)->toBe($payroll->id);
    expect($reimbursement->status)->toBe(ReimbursementStatus::PAID);
});

test('linkToPayroll throws if reimbursement not approved', function () {
    $reimbursement = Reimbursement::factory()->create([
        'employee_id' => $this->employee->id,
        'status' => ReimbursementStatus::PENDING,
    ]);

    $service = new ReimbursementService($this->createMock(ApprovalService::class));
    $service->linkToPayroll($reimbursement, 1);
})->throws(BusinessRuleException::class, 'Hanya reimbursement yang sudah disetujui penuh');

test('linkToPayroll throws if already linked to another payroll', function () {
    $payroll = Payroll::create([
        'employee_id' => $this->employee->id,
        'period' => '2026-06',
        'basic_salary' => 0,
        'total_allowance' => 0,
        'gross_salary' => 0,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 0,
        'status' => PayrollStatus::DRAFT,
    ]);
    $reimbursement = Reimbursement::factory()->create([
        'employee_id' => $this->employee->id,
        'status' => ReimbursementStatus::APPROVED,
        'payroll_id' => $payroll->id,
    ]);

    $service = new ReimbursementService($this->createMock(ApprovalService::class));
    $service->linkToPayroll($reimbursement, 999);
})->throws(BusinessRuleException::class, 'Reimbursement sudah terhubung ke payroll lain');

// ─── Model Terminal Guard ───────────────────────────────────────────────

test('reimbursement model blocks update when already APPROVED', function () {
    $reimbursement = Reimbursement::factory()->create([
        'employee_id' => $this->employee->id,
        'status' => ReimbursementStatus::APPROVED,
    ]);

    $reimbursement->update(['description' => 'changed']);
})->throws(BusinessRuleException::class, 'tidak dapat diubah');

test('reimbursement model blocks update when already PAID', function () {
    $reimbursement = Reimbursement::factory()->create([
        'employee_id' => $this->employee->id,
        'status' => ReimbursementStatus::PAID,
    ]);

    $reimbursement->update(['description' => 'changed']);
})->throws(BusinessRuleException::class, 'tidak dapat diubah');

test('reimbursement model blocks update when already REJECTED', function () {
    $reimbursement = Reimbursement::factory()->create([
        'employee_id' => $this->employee->id,
        'status' => ReimbursementStatus::REJECTED,
    ]);

    $reimbursement->update(['description' => 'changed']);
})->throws(BusinessRuleException::class, 'tidak dapat diubah');

test('reimbursement model allows update when PENDING', function () {
    $reimbursement = Reimbursement::factory()->create([
        'employee_id' => $this->employee->id,
        'status' => ReimbursementStatus::PENDING,
    ]);

    $reimbursement->update(['description' => 'still pending']);

    expect($reimbursement->description)->toBe('still pending');
});

test('linkToPayroll throws if payroll is locked', function () {
    $reimbursement = Reimbursement::factory()->create([
        'employee_id' => $this->employee->id,
        'status' => ReimbursementStatus::APPROVED,
        'payroll_id' => null,
    ]);
    $payroll = Payroll::create([
        'employee_id' => $this->employee->id,
        'period' => '2026-06',
        'basic_salary' => 0,
        'total_allowance' => 0,
        'gross_salary' => 0,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 0,
        'status' => PayrollStatus::PUBLISHED,
    ]);

    $service = new ReimbursementService($this->createMock(ApprovalService::class));
    $service->linkToPayroll($reimbursement, $payroll->id);
})->throws(BusinessRuleException::class, 'Payroll sudah dikunci');
