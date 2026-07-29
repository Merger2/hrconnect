<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Enums\PayrollStatus;
use App\Models\Payroll;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * PayrollExportService — generate Excel reports untuk pelaporan gaji.
 *
 * 3 format export (sesuai PRD §27 Reports):
 * 1. Monthly Payroll  — rekap payroll per periode/branch
 * 2. Tax 1721-A1      — bukti potong PPh 21 bulanan
 * 3. BPJS Report      — BPJS Kesehatan + Ketenagakerjaan
 *
 * Pakai OpenSpout (memory-efficient streaming) — alternative dari
 * Maatwebsite/Excel yang tidak compatible dengan Laravel 13 + PHP 8.5.
 *
 * Output: file disimpan di storage/app/private/exports/, return absolute path.
 */
class PayrollExportService
{
    /**
     * Export Monthly Payroll ke Excel.
     *
     * @return string Absolute path ke file xlsx
     */
    public function exportMonthly(string $period, ?int $branchId = null): string
    {
        $query = Payroll::query()
            ->with(['employee.position', 'employee.division', 'employee.branch'])
            ->where('period', $period)
            ->whereIn('status', [PayrollStatus::APPROVED, PayrollStatus::PAID]);

        if ($branchId !== null) {
            $query->whereHas('employee', fn ($q) => $q->where('branch_id', $branchId));
        }

        $payrolls = $query->orderBy('id')->get();

        $filename = "payroll-monthly-{$period}".($branchId ? "-branch{$branchId}" : '').'.xlsx';
        $path = $this->resolveExportPath($filename);

        return $this->writeXlsx($path, function (Writer $writer) use ($payrolls): void {
            $headerStyle = (new Style)
                ->withFontBold(true)
                ->withFontColor(Color::WHITE)
                ->withBackgroundColor('1F2937');

            $writer->addRow(Row::fromValuesWithStyle([
                'NIK', 'Nama', 'Cabang', 'Departemen', 'Posisi',
                'Gaji Pokok', 'Tunjangan', 'Lembur',
                'PPh 21', 'BPJS Kesehatan', 'BPJS Ketenagakerjaan',
                'Pinjaman', 'Denda', 'Gross', 'Total Potongan', 'Net (Take Home)',
            ], $headerStyle));

            foreach ($payrolls as $payroll) {
                $writer->addRow(Row::fromValues([
                    $payroll->employee?->employee_number ?? '-',
                    $payroll->employee?->full_name ?? '-',
                    $payroll->employee?->branch?->name ?? '-',
                    $payroll->employee?->division?->name ?? '-',
                    $payroll->employee?->position?->name ?? '-',
                    (int) $payroll->basic_salary,
                    (int) $payroll->total_allowance,
                    (int) $payroll->overtime_pay,
                    (int) $payroll->pph21,
                    (int) $payroll->bpjs_health,
                    (int) $payroll->bpjs_employment,
                    (int) $payroll->loan_deduction,
                    (int) $payroll->attendance_penalty,
                    (int) $payroll->gross_salary,
                    (int) $payroll->total_deduction,
                    (int) $payroll->net_salary,
                ]));
            }

            $summaryStyle = (new Style)->withFontBold(true)->withBackgroundColor('F3F4F6');
            $writer->addRow(Row::fromValuesWithStyle([
                'TOTAL', '', '', '', '',
                $payrolls->sum('basic_salary'),
                $payrolls->sum('total_allowance'),
                $payrolls->sum('overtime_pay'),
                $payrolls->sum('pph21'),
                $payrolls->sum('bpjs_health'),
                $payrolls->sum('bpjs_employment'),
                $payrolls->sum('loan_deduction'),
                $payrolls->sum('attendance_penalty'),
                $payrolls->sum('gross_salary'),
                $payrolls->sum('total_deduction'),
                $payrolls->sum('net_salary'),
            ], $summaryStyle));
        });
    }

    /**
     * Export 1721-A1 (bukti potong PPh 21) untuk SPT bulanan.
     *
     * Format mengikuti template DJP — kolom inti: NIK, NPWP, Nama,
     * Penghasilan Bruto, PTKP, PKP, PPh 21 dipotong.
     */
    public function export1721A1(string $period): string
    {
        $payrolls = Payroll::query()
            ->with(['employee.position'])
            ->where('period', $period)
            ->whereIn('status', [PayrollStatus::APPROVED, PayrollStatus::PAID])
            ->orderBy('id')
            ->get();

        $filename = "1721-a1-{$period}.xlsx";
        $path = $this->resolveExportPath($filename);

        return $this->writeXlsx($path, function (Writer $writer) use ($payrolls, $period): void {
            $headerStyle = (new Style)
                ->withFontBold(true)
                ->withFontColor(Color::WHITE)
                ->withBackgroundColor('1F2937');

            $titleStyle = (new Style)->withFontBold(true)->withFontSize(14);
            $writer->addRow(Row::fromValuesWithStyle([
                "BUKTI POTONG PPh 21 (1721-A1) — Periode {$period}",
            ], $titleStyle));
            $writer->addRow(Row::fromValues(['']));

            $writer->addRow(Row::fromValuesWithStyle([
                'No', 'NIK', 'NPWP', 'Nama Karyawan', 'Posisi',
                'Penghasilan Bruto', 'PPh 21 Dipotong', 'Penghasilan Neto',
            ], $headerStyle));

            $no = 1;
            foreach ($payrolls as $payroll) {
                $writer->addRow(Row::fromValues([
                    $no++,
                    $payroll->employee?->employee_number ?? '-',
                    $payroll->employee?->npwp ?? '-',
                    $payroll->employee?->full_name ?? '-',
                    $payroll->employee?->position?->name ?? '-',
                    (int) $payroll->gross_salary,
                    (int) $payroll->pph21,
                    (int) $payroll->net_salary,
                ]));
            }

            $summaryStyle = (new Style)->withFontBold(true)->withBackgroundColor('F3F4F6');
            $writer->addRow(Row::fromValuesWithStyle([
                '', '', '', 'TOTAL', '',
                $payrolls->sum('gross_salary'),
                $payrolls->sum('pph21'),
                $payrolls->sum('net_salary'),
            ], $summaryStyle));
        });
    }

    /**
     * Export BPJS Report (Kesehatan + Ketenagakerjaan) untuk pelaporan
     * iuran bulanan.
     */
    public function exportBpjsReport(string $period): string
    {
        $payrolls = Payroll::query()
            ->with(['employee'])
            ->where('period', $period)
            ->whereIn('status', [PayrollStatus::APPROVED, PayrollStatus::PAID])
            ->orderBy('id')
            ->get();

        $filename = "bpjs-report-{$period}.xlsx";
        $path = $this->resolveExportPath($filename);

        return $this->writeXlsx($path, function (Writer $writer) use ($payrolls, $period): void {
            $titleStyle = (new Style)->withFontBold(true)->withFontSize(14);
            $writer->addRow(Row::fromValuesWithStyle([
                "LAPORAN IURAN BPJS — Periode {$period}",
            ], $titleStyle));
            $writer->addRow(Row::fromValues(['']));

            $headerStyle = (new Style)
                ->withFontBold(true)
                ->withFontColor(Color::WHITE)
                ->withBackgroundColor('1F2937');

            $writer->addRow(Row::fromValuesWithStyle([
                'No', 'NIK', 'Nama Karyawan', 'Gaji Bruto',
                'BPJS Kesehatan (Karyawan)', 'BPJS Ketenagakerjaan (Karyawan)',
                'Total Iuran Karyawan',
            ], $headerStyle));

            $no = 1;
            foreach ($payrolls as $payroll) {
                $totalIuran = $payroll->bpjs_health + $payroll->bpjs_employment;

                $writer->addRow(Row::fromValues([
                    $no++,
                    $payroll->employee?->employee_number ?? '-',
                    $payroll->employee?->full_name ?? '-',
                    (int) $payroll->gross_salary,
                    (int) $payroll->bpjs_health,
                    (int) $payroll->bpjs_employment,
                    (int) $totalIuran,
                ]));
            }

            $summaryStyle = (new Style)->withFontBold(true)->withBackgroundColor('F3F4F6');
            $writer->addRow(Row::fromValuesWithStyle([
                '', '', 'TOTAL', $payrolls->sum('gross_salary'),
                $payrolls->sum('bpjs_health'),
                $payrolls->sum('bpjs_employment'),
                $payrolls->sum('bpjs_health') + $payrolls->sum('bpjs_employment'),
            ], $summaryStyle));
        });
    }

    /**
     * @param  callable(Writer): void  $write
     */
    protected function writeXlsx(string $path, callable $write): string
    {
        $writer = new Writer;
        $opened = false;

        try {
            $writer->openToFile($path);
            $opened = true;

            $write($writer);

            return $path;
        } finally {
            if ($opened) {
                $writer->close();
            }
        }
    }

    /**
     * Resolve absolute path untuk export file.
     */
    protected function resolveExportPath(string $filename): string
    {
        $dir = storage_path('app/private/exports');

        // B-23: Don't suppress mkdir errors
        if (! is_dir($dir)) {
            if (! mkdir($dir, 0755, recursive: true) && ! is_dir($dir)) {
                throw new \RuntimeException("Tidak dapat membuat direktori export: {$dir}");
            }
        }

        return $dir.'/'.$filename;
    }
}
