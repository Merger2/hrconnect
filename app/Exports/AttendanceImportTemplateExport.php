<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Template download untuk import absensi — hanya header (tanpa data), kolom
 * diselaraskan dengan kontrak WithHeadingRow di AttendanceImport. Sebelumnya
 * tombol "Download Template" hanya toast info tanpa file nyata (stub).
 */
class AttendanceImportTemplateExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        return new Collection;
    }

    public function headings(): array
    {
        return [
            'employee_number',
            'employee_id',
            'date',
            'clock_in',
            'clock_out',
            'status',
            'notes',
            'latitude',
            'longitude',
        ];
    }
}
