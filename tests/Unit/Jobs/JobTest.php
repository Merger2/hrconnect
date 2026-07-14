<?php

use App\Enums\KnowledgeBaseStatus;
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

describe('GenerateEmployeePayrollJob', function () {
    it('calls calculator service with correct params', function () {
        $calculator = Mockery::mock(PayrollCalculatorService::class);
        $calculator->shouldReceive('generatePayroll')
            ->with(Mockery::on(fn ($e) => $e->id === $this->employee->id), '2026-06')
            ->once();

        $job = new GenerateEmployeePayrollJob($this->employee, '2026-06');
        $job->handle($calculator);
    });

    it('rolls back reimbursements on failed', function () {
        $category = ReimbursementCategory::factory()->for($this->company)->create();
        $reimbursement = Reimbursement::factory()->create([
            'employee_id' => $this->employee->id,
            'category_id' => $category->id,
            'payroll_id' => null,
            'status' => ReimbursementStatus::PAID,
        ]);

        $job = new GenerateEmployeePayrollJob($this->employee, '2026-06');
        $job->failed(new RuntimeException('Test failure'));

        $reimbursement->refresh();
        expect($reimbursement->status)->toBe(ReimbursementStatus::APPROVED);
    });

    it('has correct queue configuration', function () {
        $job = new GenerateEmployeePayrollJob($this->employee, '2026-06');

        expect($job->queue)->toBe('payroll_high');
        expect($job->tries)->toBe(3);
        expect($job->timeout)->toBe(120);
        expect($job->backoff)->toBe([10, 30, 60]);
    });
});

describe('GeneratePayslipPdfJob', function () {
    it('calls pdf service with correct payroll', function () {
        $payroll = Payroll::factory()->create([
            'employee_id' => $this->employee->id,
        ]);
        $pdfService = Mockery::mock(PayslipPdfService::class);
        $pdfService->shouldReceive('generateAndStore')
            ->with(Mockery::on(fn ($p) => $p->id === $payroll->id))
            ->once();

        $job = new GeneratePayslipPdfJob($payroll);
        $job->handle($pdfService);
    });

    it('clears pdf_path on failed', function () {
        $payroll = Payroll::factory()->create([
            'employee_id' => $this->employee->id,
            'pdf_path' => 'payslips/test.pdf',
        ]);

        $job = new GeneratePayslipPdfJob($payroll);
        $job->failed(new RuntimeException('PDF generation failed'));

        $payroll->refresh();
        expect($payroll->pdf_path)->toBeNull();
    });

    it('has correct queue configuration', function () {
        $payroll = Payroll::factory()->create(['employee_id' => $this->employee->id]);
        $job = new GeneratePayslipPdfJob($payroll);

        expect($job->tries)->toBe(3);
        expect($job->timeout)->toBe(120);
        expect($job->backoff)->toBe([10, 30, 60]);
    });
});

describe('ProcessKnowledgeBaseEmbedding', function () {
    it('calls embedding service with correct KB', function () {
        $kb = new KnowledgeBase;
        $kb->title = 'Test KB';
        $kb->content = 'Test content for knowledge base embedding job unit test.';
        $kb->knowledgeable()->associate($this->employee);
        $kb->save();

        $embeddingService = Mockery::mock(EmbeddingService::class);
        $embeddingService->shouldReceive('processKnowledgeBase')
            ->with(Mockery::on(fn ($k) => $k->id === $kb->id))
            ->once();

        $job = new ProcessKnowledgeBaseEmbedding($kb);
        $job->handle($embeddingService);
    });

    it('sets status to ERROR on failed', function () {
        $kb = new KnowledgeBase;
        $kb->title = 'Test KB Error';
        $kb->content = 'Test content for knowledge base embedding error test.';
        $kb->status = KnowledgeBaseStatus::PROCESSING;
        $kb->knowledgeable()->associate($this->employee);
        $kb->save();

        $job = new ProcessKnowledgeBaseEmbedding($kb);
        $job->failed(new RuntimeException('Embedding failed'));

        $kb->refresh();
        expect($kb->status)->toBe(KnowledgeBaseStatus::ERROR);
    });

    it('has correct queue configuration', function () {
        $kb = new KnowledgeBase;
        $kb->title = 'Test KB Config';
        $kb->content = 'Test content for knowledge base embedding config test.';
        $kb->knowledgeable()->associate($this->employee);
        $kb->save();

        $job = new ProcessKnowledgeBaseEmbedding($kb);

        expect($job->tries)->toBe(3);
        expect($job->timeout)->toBe(300);
        expect($job->backoff)->toBe([30, 60, 120]);
    });
});
