<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Carbon\CarbonImmutable;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * PayslipPdfService — generate PDF slip gaji untuk Payroll.
 *
 * Pakai Spatie Laravel PDF (Browsershot/Puppeteer) untuk render Blade
 * template ke PDF dengan kualitas tinggi (Tailwind/CSS modern support).
 *
 * Template: resources/views/payroll/payslip.blade.php (2 kolom Pendapatan/Potongan
 * dengan Take Home Pay di bawah, sesuai PRD §11.8).
 *
 * Storage: file di-save ke storage/app/private/payslips/{period}/{employee_number}.pdf
 * supaya tidak public (perlu Sanctum auth + Policy::downloadPayslip via streaming).
 */
class PayslipPdfService
{
    /**
     * Generate PDF binary content untuk single Payroll.
     */
    public function generate(Payroll $payroll): string
    {
        $payroll->loadMissing(['employee.position', 'employee.department', 'employee.branch']);

        $data = $this->buildTemplateData($payroll);

        return Pdf::view('payroll.payslip', $data)
            ->format('A4')
            ->margins(20, 20, 20, 20)
            ->base64();
    }

    /**
     * Generate + save ke storage/app/private/payslips/, return absolute path.
     */
    public function generateAndStore(Payroll $payroll): string
    {
        $payroll->loadMissing(['employee.position', 'employee.department', 'employee.branch']);

        $data = $this->buildTemplateData($payroll);

        $period = $payroll->period;
        $empNumber = $payroll->employee?->employee_number ?? 'unknown';
        $relativePath = "payslips/{$period}/{$empNumber}.pdf";
        $absolutePath = storage_path('app/private/'.$relativePath);

        @mkdir(dirname($absolutePath), 0755, recursive: true);

        Pdf::view('payroll.payslip', $data)
            ->format('A4')
            ->margins(20, 20, 20, 20)
            ->save($absolutePath);

        return $absolutePath;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildTemplateData(Payroll $payroll): array
    {
        $period = CarbonImmutable::createFromFormat('Y-m', $payroll->period);

        // Get extra income items (non-standard, dari payroll_items kalau ada)
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
