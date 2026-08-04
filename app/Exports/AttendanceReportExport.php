<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceReportExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    protected $records;

    protected $meta;

    public function __construct($records, $meta = [])
    {
        $this->records = $records;
        $this->meta = $meta;
    }

    public function collection(): Collection
    {
        return collect($this->records);
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Department',
            'Position',
            'Date',
            'Clock In',
            'Clock Out',
            'Status',
            'Total Hours',
            'Overtime Hours',
            'Notes',
            'Report Period',
        ];
    }

    public function map($attendance): array
    {
        $employee = $attendance->employee;
        $clockIn = $attendance->clock_in?->format('H:i:s') ?? '-';
        $clockOut = $attendance->clock_out?->format('H:i:s') ?? '-';
        $totalHours = $attendance->clock_in && $attendance->clock_out
            ? round($attendance->clock_in->floatDiffInHours($attendance->clock_out), 2)
            : 0;

        return [
            $employee->employee_number ?? '-',
            $employee->user?->name ?? $employee->full_name ?? '-',
            $employee->division?->name ?? '-',
            $employee->position?->name ?? '-',
            $attendance->date?->format('Y-m-d') ?? '-',
            $clockIn,
            $clockOut,
            ucfirst($attendance->status ?? 'present'),
            $totalHours,
            $attendance->overtime_hours ?? 0,
            $attendance->notes ?? '-',
            $this->meta['period'] ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '0d9488']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 20,
            'C' => 15,
            'D' => 15,
            'E' => 12,
            'F' => 10,
            'G' => 10,
            'H' => 10,
            'I' => 12,
            'J' => 12,
            'K' => 30,
            'L' => 15,
        ];
    }
}
