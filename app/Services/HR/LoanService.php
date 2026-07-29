<?php

declare(strict_types=1);

namespace App\Services\HR;

use App\Enums\LoanStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\Loan;
use App\Support\ApprovalService;
use Illuminate\Support\Facades\DB;

class LoanService
{
    public function __construct(
        protected ApprovalService $approvalService,
    ) {}

    public function createLoan(Employee $employee, array $data): Loan
    {
        $monthlyInstallment = $this->calculateMonthlyInstallment(
            (float) $data['amount'],
            (float) ($data['interest_rate'] ?? 0),
            (int) $data['tenor_months']
        );

        $loan = DB::transaction(function () use ($employee, $data, $monthlyInstallment) {
            $loan = Loan::create([
                'employee_id' => $employee->id,
                'created_by' => auth()->id(),
                'amount' => $data['amount'],
                'interest_rate' => $data['interest_rate'] ?? 0,
                'tenor_months' => $data['tenor_months'],
                'monthly_installment' => $monthlyInstallment,
                'status' => LoanStatus::PENDING,
            ]);

            $this->approvalService->createApprovalWorkflow($loan);

            return $loan;
        });

        return $loan->fresh(['employee:id,employee_number,full_name', 'approvals.approver:id,full_name']);
    }

    public function updateLoan(Loan $loan, array $data): Loan
    {
        if ($loan->status !== LoanStatus::PENDING) {
            throw new BusinessRuleException('Hanya pinjaman dengan status PENDING yang dapat diubah.');
        }

        if (isset($data['amount']) || isset($data['interest_rate']) || isset($data['tenor_months'])) {
            $data['monthly_installment'] = $this->calculateMonthlyInstallment(
                (float) ($data['amount'] ?? $loan->amount),
                (float) ($data['interest_rate'] ?? $loan->interest_rate),
                (int) ($data['tenor_months'] ?? $loan->tenor_months)
            );
        }

        $loan->update($data);

        return $loan->fresh();
    }

    public function cancelLoan(Loan $loan): void
    {
        if (! in_array($loan->status->value, [LoanStatus::PENDING->value, LoanStatus::APPROVED->value])) {
            throw new BusinessRuleException('Pinjaman tidak dapat dibatalkan pada status saat ini.');
        }

        $loan->update(['status' => LoanStatus::CANCELLED]);
    }

    protected function calculateMonthlyInstallment(float $amount, float $interestRate, int $tenorMonths): float
    {
        if ($tenorMonths <= 0) {
            throw new BusinessRuleException('Tenor harus lebih dari 0 bulan.');
        }

        if ($interestRate <= 0) {
            return round($amount / $tenorMonths, 2);
        }

        $monthlyRate = $interestRate / 100 / 12;
        $payment = $amount * ($monthlyRate * pow(1 + $monthlyRate, $tenorMonths))
            / (pow(1 + $monthlyRate, $tenorMonths) - 1);

        return round($payment, 2);
    }
}
