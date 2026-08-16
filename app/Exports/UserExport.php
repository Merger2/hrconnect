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

class UserExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
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
            'ID',
            'Name',
            'Email',
            'Roles',
            'Employee Number',
            'Employee Name',
            'Department',
            'Position',
            'Status',
            'Email Verified',
            'Created At',
        ];
    }

    public function map($user): array
    {
        $employee = $user->employee;

        return [
            $user->id,
            $user->name,
            $user->email,
            // BUG FIX (2026-08-08): UserExport memanggil getRoleNames() — method
            // Spatie laravel-permission yang TIDAK ada di User (project ini pakai
            // HasRolePermissions + morphToMany roles). Sebelumnya export users
            // selalu 500 (BadMethodCallException).
            $user->roles->pluck('name')->implode(', '),
            $employee->employee_number ?? '-',
            $employee->full_name ?? '-',
            $employee->division->name ?? '-',
            $employee->position->name ?? '-',
            $employee->status ?? '-',
            $user->email_verified_at ? 'Yes' : 'No',
            $user->created_at?->format('Y-m-d H:i:s') ?? '-',
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
            'A' => 8,
            'B' => 25,
            'C' => 30,
            'D' => 25,
            'E' => 20,
            'F' => 25,
            'G' => 20,
            'H' => 20,
            'I' => 15,
            'J' => 15,
            'K' => 20,
        ];
    }
}
