<?php

namespace Database\Factories;

use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'period' => now()->format('Y-m'),
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
        ];
    }
}
