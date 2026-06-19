<?php

use App\Enums\ReimbursementStatus;
use App\Jobs\GenerateEmployeePayrollJob;
use App\Jobs\GeneratePayslipPdfJob;
use App\Jobs\ProcessKnowledgeBaseEmbedding;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\KnowledgeBase;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\User;
use App\Services\EmbeddingService;
use App\Services\PayrollCalculatorService;
use App\Services\PayslipPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class)->group('jobs', 'unit');

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->branch = Branch::factory()->for($this->company)->create();
    $this->department = Department::factory()->for($this->branch)->create();
    $this->position = Position::factory()->for($this->department)->create();
    $this->user = User::factory()->create();

    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
    ]);
});

// ─── Exception Propagation ───────────────────────────────────────────

test('GenerateEmployeePayrollJob re-throws exception from service', function () {
    $calculator = Mockery::mock(PayrollCalculatorService::class);
    $calculator->shouldReceive('generatePayroll')
        ->andThrow(new RuntimeException('Service error'));

    $job = new GenerateEmployeePayrollJob($this->employee, '2026-06');

    expect(fn () => $job->handle($calculator))
        ->toThrow(RuntimeException::class, 'Service error');
});

test('GeneratePayslipPdfJob re-throws exception from service', function () {
    $payroll = Payroll::factory()->create([
        'employee_id' => $this->employee->id,
    ]);
    $pdfService = Mockery::mock(PayslipPdfService::class);
    $pdfService->shouldReceive('generateAndStore')
        ->andThrow(new RuntimeException('PDF error'));

    $job = new GeneratePayslipPdfJob($payroll);

    expect(fn () => $job->handle($pdfService))
        ->toThrow(RuntimeException::class, 'PDF error');
});

test('ProcessKnowledgeBaseEmbedding re-throws exception from service', function () {
    $kb = new KnowledgeBase;
    $kb->title = 'Test KB';
    $kb->content = 'Content';
    $kb->knowledgeable()->associate($this->employee);
    $kb->save();

    $embeddingService = Mockery::mock(EmbeddingService::class);
    $embeddingService->shouldReceive('processKnowledgeBase')
        ->andThrow(new RuntimeException('Embedding error'));

    $job = new ProcessKnowledgeBaseEmbedding($kb);

    expect(fn () => $job->handle($embeddingService))
        ->toThrow(RuntimeException::class, 'Embedding error');
});

// ─── Logging ─────────────────────────────────────────────────────────

test('GenerateEmployeePayrollJob logs on success', function () {
    Log::spy();

    $calculator = Mockery::mock(PayrollCalculatorService::class);
    $calculator->shouldReceive('generatePayroll')->once();

    $job = new GenerateEmployeePayrollJob($this->employee, '2026-06');
    $job->handle($calculator);

    Log::shouldHaveReceived('info')
        ->withArgs(fn ($msg) => str_contains($msg, 'Payroll generated successfully'))
        ->once();
});

test('GenerateEmployeePayrollJob logs on failure', function () {
    Log::spy();

    $job = new GenerateEmployeePayrollJob($this->employee, '2026-06');
    $job->failed(new RuntimeException('DB connection lost'));

    Log::shouldHaveReceived('error')
        ->withArgs(fn ($msg) => str_contains($msg, 'Payroll generation failed permanently'))
        ->once();
});

test('GeneratePayslipPdfJob logs on failure', function () {
    Log::spy();

    $payroll = Payroll::factory()->create(['employee_id' => $this->employee->id]);
    $job = new GeneratePayslipPdfJob($payroll);
    $job->failed(new RuntimeException('Disk full'));

    Log::shouldHaveReceived('error')
        ->withArgs(fn ($msg) => str_contains($msg, 'PDF generation failed permanently'))
        ->once();
});

test('ProcessKnowledgeBaseEmbedding logs on success', function () {
    Log::spy();

    $kb = new KnowledgeBase;
    $kb->title = 'Test KB';
    $kb->content = 'Content';
    $kb->knowledgeable()->associate($this->employee);
    $kb->save();

    $embeddingService = Mockery::mock(EmbeddingService::class);
    $embeddingService->shouldReceive('processKnowledgeBase')->once();

    $job = new ProcessKnowledgeBaseEmbedding($kb);
    $job->handle($embeddingService);

    Log::shouldHaveReceived('info')
        ->withArgs(fn ($msg) => str_contains($msg, 'KB embedding generated'))
        ->once();
});

test('GenerateEmployeePayrollJob failed() rollback reimbursements from PAID to APPROVED', function () {
    $category = ReimbursementCategory::create([
        'company_id' => $this->company->id,
        'name' => 'Transportasi',
        'code' => 'TRN',
        'is_active' => true,
    ]);

    $reimbursement = Reimbursement::create([
        'employee_id' => $this->employee->id,
        'category_id' => $category->id,
        'title' => 'Test Reimbursement',
        'expense_date' => now()->format('Y-m-d'),
        'amount' => 100_000,
        'description' => 'Test',
        'payroll_id' => null,
        'status' => ReimbursementStatus::PAID,
    ]);

    $job = new GenerateEmployeePayrollJob($this->employee, '2026-06');
    $job->failed(new RuntimeException('Test failure'));

    $reimbursement->refresh();
    expect($reimbursement->status)->toBe(ReimbursementStatus::APPROVED);
});

test('GenerateEmployeePayrollJob failed() does not rollback reimbursements with payroll_id', function () {
    $category = ReimbursementCategory::create([
        'company_id' => $this->company->id,
        'name' => 'Konsumsi',
        'code' => 'KON',
        'is_active' => true,
    ]);

    $payroll = Payroll::create([
        'employee_id' => $this->employee->id,
        'period' => '2026-06',
        'basic_salary' => 5_000_000,
        'total_allowance' => 0,
        'gross_salary' => 5_000_000,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 5_000_000,
        'status' => 'draft',
    ]);

    $reimbursement = Reimbursement::create([
        'employee_id' => $this->employee->id,
        'category_id' => $category->id,
        'title' => 'Test Reimbursement 2',
        'expense_date' => now()->format('Y-m-d'),
        'amount' => 200_000,
        'description' => 'Test 2',
        'payroll_id' => $payroll->id,
        'status' => ReimbursementStatus::PAID,
    ]);

    $job = new GenerateEmployeePayrollJob($this->employee, '2026-06');
    $job->failed(new RuntimeException('Test failure'));

    $reimbursement->refresh();
    expect($reimbursement->status)->toBe(ReimbursementStatus::PAID);
});

test('ProcessKnowledgeBaseEmbedding logs on failure', function () {
    Log::spy();

    $kb = new KnowledgeBase;
    $kb->title = 'Test KB';
    $kb->content = 'Content';
    $kb->knowledgeable()->associate($this->employee);
    $kb->save();

    $job = new ProcessKnowledgeBaseEmbedding($kb);
    $job->failed(new RuntimeException('API timeout'));

    Log::shouldHaveReceived('error')
        ->withArgs(fn ($msg) => str_contains($msg, 'KB embedding job failed permanently'))
        ->once();
});

// ─── Serialization ───────────────────────────────────────────────────

test('GenerateEmployeePayrollJob survives serialization', function () {
    $job = new GenerateEmployeePayrollJob($this->employee, '2026-06');
    $serialized = serialize($job);
    /** @var GenerateEmployeePayrollJob $restored */
    $restored = unserialize($serialized);

    expect($restored->employee->id)->toBe($this->employee->id);
    expect($restored->period)->toBe('2026-06');
    expect($restored->tries)->toBe(3);
    expect($restored->timeout)->toBe(120);
    expect($restored->queue)->toBe('payroll_high');
});

test('GeneratePayslipPdfJob survives serialization', function () {
    $payroll = Payroll::factory()->create(['employee_id' => $this->employee->id]);
    $job = new GeneratePayslipPdfJob($payroll);
    $serialized = serialize($job);
    /** @var GeneratePayslipPdfJob $restored */
    $restored = unserialize($serialized);

    expect($restored->payroll->id)->toBe($payroll->id);
    expect($restored->tries)->toBe(3);
});

test('ProcessKnowledgeBaseEmbedding survives serialization', function () {
    $kb = new KnowledgeBase;
    $kb->title = 'Serial Test';
    $kb->content = 'Content';
    $kb->knowledgeable()->associate($this->employee);
    $kb->save();

    $job = new ProcessKnowledgeBaseEmbedding($kb);
    $serialized = serialize($job);
    /** @var ProcessKnowledgeBaseEmbedding $restored */
    $restored = unserialize($serialized);

    expect($restored->knowledgeBase->id)->toBe($kb->id);
    expect($restored->tries)->toBe(3);
});
