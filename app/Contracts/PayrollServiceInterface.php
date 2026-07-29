<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Enterprise Payroll Service Interface (Stub for Thesis)
 * Originally from enterprise edition - replaced with unlocked stub
 */
interface PayrollServiceInterface
{
    public function calculate(int $employeeId, int $month, int $year): array;

    public function calculateBatch(array $employeeIds, int $month, int $year): array;

    public function generatePayslip(int $payrollId): string;
}
