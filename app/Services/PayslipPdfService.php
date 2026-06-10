<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;

class PayslipPdfService
{
    public function generate(Payroll $payroll): string
    {
        $payroll->loadMissing(['employee.position', 'employee.department', 'employee.branch']);

        $data = $this->buildTemplateData($payroll);

        return Pdf::loadView('payroll.payslip', $data)
            ->setPaper('A4')
            ->setOptions(['defaultFont' => 'sans-serif', 'isRemoteEnabled' => false])
            ->output();
    }

    public function generateAndStore(Payroll $payroll): string
    {
        $payroll->loadMissing(['employee.position', 'employee.department', 'employee.branch']);

        $data = $this->buildTemplateData($payroll);

        $period = $payroll->period;
        $empNumber = $payroll->employee?->employee_number ?? 'unknown';
        $relativePath = "payslips/{$period}/{$empNumber}.pdf";
        $absolutePath = storage_path('app/private/'.$relativePath);

        // B-23: Don't suppress mkdir errors — throw if directory can't be created
        $dir = dirname($absolutePath);
        if (! is_dir($dir)) {
            if (! mkdir($dir, 0755, recursive: true) && ! is_dir($dir)) {
                throw new \RuntimeException("Tidak dapat membuat direktori payslip: {$dir}");
            }
        }

        Pdf::loadView('payroll.payslip', $data)
            ->setPaper('A4')
            ->setOptions(['defaultFont' => 'sans-serif', 'isRemoteEnabled' => false])
            ->save($absolutePath);

        $payroll->updateQuietly(['pdf_path' => $relativePath]);

        return $absolutePath;
    }

    public function getPayslipPath(Payroll $payroll): ?string
    {
        if ($payroll->pdf_path && file_exists(storage_path('app/private/'.$payroll->pdf_path))) {
            return storage_path('app/private/'.$payroll->pdf_path);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildTemplateData(Payroll $payroll): array
    {
        $period = CarbonImmutable::createFromFormat('Y-m', $payroll->period);

        $extraIncome = PayrollItem::query()
            ->where('payroll_id', $payroll->id)
            ->where('type', 'income')
            ->get(['name', 'amount'])
            ->map(fn (PayrollItem $item) => [
                'name' => $item->name,
                'amount' => (int) $item->amount,
            ])
            ->all();

        $company = Company::query()->first();

        return [
            'payroll' => $payroll,
            'period_label' => $period->locale('id')->translatedFormat('F Y'),
            'company' => $company ? [
                'name' => $company->name,
                'address' => $company->address ?? 'Jakarta, Indonesia',
                'npwp' => $company->npwp ?? '-',
            ] : null,
            'extra_income' => $extraIncome,
            'generated_at' => now()->locale('id')->translatedFormat('d F Y H:i'),
        ];
    }
}
