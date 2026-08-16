<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Template download untuk import user — hanya header (tanpa data), kolom
 * diselaraskan dengan kontrak WithHeadingRow di UserImport. Sebelumnya
 * tombol "Download Template" hanya toast info tanpa file nyata (stub).
 */
class UserImportTemplateExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        return new Collection;
    }

    public function headings(): array
    {
        return [
            'email',
            'name',
            'password',
            'role',
            'employee_number',
        ];
    }
}
