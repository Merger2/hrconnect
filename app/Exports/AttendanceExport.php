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

class AttendanceExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }

    public function collection(): Collection
    {
        return collect($this->records);
    }

    public function headings(): array
    {
        return [
            'Employee Number',
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
            ucfirst((string) ($attendance->status?->value ?? 'present')),
            $totalHours,
            $attendance->overtime_hours ?? 0,
            $attendance->notes ?? '-',
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
            'A' => 20,
            'B' => 25,
            'C' => 20,
            'D' => 20,
            'E' => 15,
            'F' => 12,
            'G' => 12,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 30,
        ];
    }
}
