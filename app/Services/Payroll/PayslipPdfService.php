<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\Company;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Support\MailBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

class PayslipPdfService
{
    public function generate(Payroll $payroll, ?string $password = null): string
    {
        $payroll->loadMissing(['employee.position', 'employee.division', 'employee.branch']);

        $data = $this->buildTemplateData($payroll);
        $pdf = Pdf::loadView('pdf.payslip', $data)
            ->setPaper('A4')
            ->setOptions(['defaultFont' => 'sans-serif', 'isRemoteEnabled' => false]);

        if ($password) {
            $this->applyEncryption($pdf, $password);
        }

        return $pdf->output();
    }

    public function generateAndStore(Payroll $payroll, ?string $password = null): string
    {
        $payroll->loadMissing(['employee.position', 'employee.division', 'employee.branch']);

        $data = $this->buildTemplateData($payroll);
        $period = $payroll->period;
        $empNumber = $payroll->employee?->employee_number ?? 'unknown';
        $relativePath = "payslips/{$period}/{$empNumber}.pdf";
        $absolutePath = storage_path('app/private/'.$relativePath);

        $dir = dirname($absolutePath);
        if (! is_dir($dir)) {
            if (! mkdir($dir, 0755, recursive: true) && ! is_dir($dir)) {
                throw new \RuntimeException("Tidak dapat membuat direktori payslip: {$dir}");
            }
        }

        $pdf = Pdf::loadView('pdf.payslip', $data)
            ->setPaper('A4')
            ->setOptions(['defaultFont' => 'sans-serif', 'isRemoteEnabled' => false]);

        if ($password) {
            $this->applyEncryption($pdf, $password);
        }

        file_put_contents($absolutePath, $pdf->output());

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

    protected function applyEncryption(\Barryvdh\DomPDF\PDF $pdf, string $password): void
    {
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();

        if (method_exists($canvas, 'get_cpdf')) {
            $cpdf = $canvas->get_cpdf();
            $cpdf->setEncryption($password, $password);
        }
    }

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

        $logoSrc = null;
        if ($company && $company->logo) {
            $logoPath = Storage::disk('public')->path($company->logo);
            if (is_file($logoPath) && is_readable($logoPath)) {
                $logoSrc = $logoPath;
            }
        }
        if (! $logoSrc) {
            $logoSrc = MailBranding::logoPdfSource();
        }

        return [
            'payroll' => $payroll,
            'period_label' => $period->locale('id')->translatedFormat('F Y'),
            'company' => $company ? [
                'logo' => $logoSrc,
                'name' => $company->name,
                'address' => $company->address ?? 'Jakarta, Indonesia',
                'npwp' => $company->npwp ?? '-',
            ] : [
                'logo' => null,
                'name' => config('app.name'),
                'address' => '',
                'npwp' => '-',
            ],
            'extra_income' => $extraIncome,
            'generated_at' => now()->locale('id')->translatedFormat('d F Y H:i'),
        ];
    }
}
