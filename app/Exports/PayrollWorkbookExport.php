<?php

namespace App\Exports;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayrollWorkbookExport implements WithMultipleSheets
{
    protected User $user;

    protected array $filters;

    public function __construct(User $user, array $filters = [])
    {
        $this->user = $user;
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        $payrolls = $this->getPayrolls();

        return [
            new PayrollSummarySheet($payrolls),
            new PaymentInstructionsSheet($payrolls),
            new CoretaxPph21Sheet($payrolls),
        ];
    }

    protected function getPayrolls(): Collection
    {
        return Payroll::with('employee')
            ->where('status', $this->filters['status'])
            ->where('period', sprintf('%04d-%02d', $this->filters['year'], $this->filters['month']))
            ->get();
    }
}

class PayrollSummarySheet implements FromCollection, WithHeadings, WithTitle
{
    protected Collection $payrolls;

    public function __construct(Collection $payrolls)
    {
        $this->payrolls = $payrolls;
    }

    public function collection(): \Illuminate\Support\Collection
    {
        return $this->payrolls->map(function ($payroll) {
            $employee = $payroll->employee;

            return [
                'Employee Name' => $employee?->full_name ?? '',
                'NIP' => $employee?->nip ?? '',
                'Period' => $payroll->period,
                'Basic Salary' => (float) $payroll->basic_salary,
                'Allowances' => (float) $payroll->total_allowance,
                'Overtime' => (float) $payroll->overtime_pay,
                'Gross Salary' => (float) $payroll->gross_salary,
                'BPJS Health' => (float) $payroll->bpjs_health,
                'BPJS Employment' => (float) $payroll->bpjs_employment,
                'PPh21' => (float) $payroll->pph21,
                'Total Deduction' => (float) $payroll->total_deduction,
                'Net Salary' => (float) $payroll->net_salary,
                'Status' => $payroll->status->value ?? $payroll->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee Name', 'NIP', 'Period', 'Basic Salary', 'Allowances',
            'Overtime', 'Gross Salary', 'BPJS Health', 'BPJS Employment',
            'PPh21', 'Total Deduction', 'Net Salary', 'Status',
        ];
    }

    public function title(): string
    {
        return 'Payroll Summary';
    }

    public function array(): array
    {
        return array_merge([$this->headings()], $this->collection()->toArray());
    }
}

class PaymentInstructionsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected Collection $payrolls;

    public function __construct(Collection $payrolls)
    {
        $this->payrolls = $payrolls;
    }

    public function collection(): \Illuminate\Support\Collection
    {
        return $this->payrolls->map(function ($payroll) {
            $employee = $payroll->employee;

            if (! $employee || ! $employee->bank_account_number) {
                return null;
            }

            $periodParts = explode('-', $payroll->period);
            $yearMonth = count($periodParts) === 2 ? $periodParts[0].$periodParts[1] : date('Ym');

            $ref = sprintf(
                'PAY-%s-%s',
                $yearMonth,
                $employee->nik ?? $employee->nip ?? $employee->employee_number ?? $employee->id
            );

            return [
                'Bank Name' => $employee->bank_name ?? '',
                'Bank Account Name' => $employee->bank_account_name ?? '',
                'Bank Account Number' => $employee->bank_account_number ?? '',
                'Amount' => (float) $payroll->net_salary,
                'Reference' => $ref,
                'Bank Code' => $employee->bank_code ?? '',
            ];
        })->filter()->values();
    }

    public function headings(): array
    {
        return ['Bank Name', 'Bank Account Name', 'Bank Account Number', 'Amount', 'Reference'];
    }

    public function title(): string
    {
        return 'Payment Instructions';
    }

    public function array(): array
    {
        return array_merge([$this->headings()], $this->collection()->toArray());
    }
}

class CoretaxPph21Sheet implements FromCollection, WithHeadings, WithTitle
{
    protected Collection $payrolls;

    public function __construct(Collection $payrolls)
    {
        $this->payrolls = $payrolls;
    }

    public function collection(): \Illuminate\Support\Collection
    {
        return $this->payrolls->map(function ($payroll) {
            $employee = $payroll->employee;
            $basicSalary = (float) $payroll->basic_salary;
            $allowance = (float) $payroll->total_allowance;
            $overtime = (float) $payroll->overtime_pay;
            $gross = $basicSalary + $allowance + $overtime;
            $nonTaxable = $allowance;
            $taxable = $gross - $nonTaxable;

            return [
                'Employee NIP' => $employee?->nip ?? '',
                'Employee Name' => $employee?->full_name ?? '',
                'Period' => $payroll->period,
                'Gross Income' => $gross,
                'Taxable Income' => max(0, $taxable),
                'Non-Taxable Income' => max(0, $nonTaxable),
                'PPh21' => (float) $payroll->pph21,
                'BPJS Employee Total' => (float) ($payroll->bpjs_health + $payroll->bpjs_employment),
                'BPJS Employer Total' => 0,
                'Status' => $payroll->status->value ?? $payroll->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee NIP', 'Employee Name', 'Period', 'Gross Income',
            'Taxable Income', 'Non-Taxable Income', 'PPh21',
            'BPJS Employee Total', 'BPJS Employer Total', 'Status',
        ];
    }

    public function title(): string
    {
        return 'Coretax Pph21';
    }

    public function array(): array
    {
        return array_merge([$this->headings()], $this->collection()->toArray());
    }
}
