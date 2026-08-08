<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Payroll;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

final class PayrollPaymentInstructionService
{
    /**
     * Bangun baris instruksi pembayaran (transfer bank) dari daftar payroll.
     * Karyawan tanpa nomor rekening di-skip.
     *
     * @return array<int, array{
     *     employee_id:int,
     *     nik:?string,
     *     name:string,
     *     bank_name:?string,
     *     bank_account_number:?string,
     *     amount:float,
     *     period:?string,
     *     reference:string,
     * }>
     */
    public function rows(EloquentCollection $payrolls): array
    {
        return $payrolls
            ->load('employee')
            ->filter(fn (Payroll $payroll): bool => filled($payroll->employee?->bank_account_number))
            ->map(fn (Payroll $payroll): array => [
                'employee_id' => $payroll->employee_id,
                'nik' => $payroll->employee?->nik,
                'name' => $payroll->employee->full_name
                    ?? $payroll->employee?->user->name
                    ?? '-',
                'bank_name' => $payroll->employee?->bank_name,
                'bank_account_number' => $payroll->employee?->bank_account_number,
                'amount' => (float) $payroll->net_salary,
                'period' => $payroll->period,
                'reference' => $this->reference($payroll),
            ])
            ->values()
            ->toArray();
    }

    private function reference(Payroll $payroll): string
    {
        $period = str_replace('-', '', (string) $payroll->period);
        $nik = (string) ($payroll->employee->nik ?? 'EMP');

        return sprintf('PAY-%s-%s', $period, $nik);
    }
}
