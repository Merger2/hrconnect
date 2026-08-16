<?php

use App\Enums\PayrollItemType;
use App\Enums\PayrollStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Position;
use App\Models\User;
use App\Services\Payroll\PayslipPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('payslip', 'unit');

beforeEach(function () {
    $company = Company::factory()->create(['name' => 'PT Test', 'npwp' => '01.234.567.8-901.000']);
    $branch = Branch::factory()->for($company)->create();
    $division = Division::factory()->for($branch)->create(['name' => 'IT Department']);
    $position = Position::factory()->for($division)->create(['name' => 'Developer']);
    $user = User::factory()->create(['name' => 'John Doe']);
    $employee = Employee::factory()->for($company)->for($division)->for($position)->create([
        'user_id' => $user->id,
        'employee_number' => 'EMP-001',
        'full_name' => 'John Doe',
        'basic_salary' => 10_000_000,
    ]);

    $this->payroll = Payroll::factory()->create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 10_000_000,
        'total_allowance' => 2_000_000,
        'gross_salary' => 12_000_000,
        'overtime_pay' => 500_000,
        'pph21' => 1_200_000,
        'bpjs_health' => 100_000,
        'bpjs_employment' => 150_000,
        'loan_deduction' => 500_000,
        'attendance_penalty' => 0,
        'total_deduction' => 1_950_000,
        'net_salary' => 10_050_000,
        'status' => PayrollStatus::APPROVED,
    ]);

    PayrollItem::factory()->count(3)->create([
        'payroll_id' => $this->payroll->id,
        'type' => PayrollItemType::ALLOWANCE,
    ]);
});

it('generates valid PDF output', function () {
    $service = app(PayslipPdfService::class);
    $pdf = $service->generate($this->payroll);

    expect($pdf)->toBeString();
    expect(strlen($pdf))->toBeGreaterThan(1000);
    expect(str_starts_with($pdf, '%PDF-'))->toBeTrue();
});

it('generates password-protected PDF', function () {
    $service = app(PayslipPdfService::class);
    $pdf = $service->generate($this->payroll, 'rahasia123');

    expect($pdf)->toBeString();
    expect(strlen($pdf))->toBeGreaterThan(1000);
    expect(str_starts_with($pdf, '%PDF-'))->toBeTrue();
});

it('stores PDF to storage and updates payroll', function () {
    $service = app(PayslipPdfService::class);
    $path = $service->generateAndStore($this->payroll);

    expect($path)->toBeString();
    expect(file_exists($path))->toBeTrue();

    $this->payroll->refresh();
    expect($this->payroll->pdf_path)->not->toBeNull();
    expect(str_contains($this->payroll->pdf_path, 'payslips/'))->toBeTrue();
});

it('generates valid PDF structure', function () {
    $service = app(PayslipPdfService::class);
    $pdf = $service->generate($this->payroll);

    expect($pdf)->toContain('%PDF-');
    expect($pdf)->toContain('/Type /Catalog');
    expect($pdf)->toContain('/Type /Page');
    expect($pdf)->toContain('/Type /Pages');
});
