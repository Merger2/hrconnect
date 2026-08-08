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

class ActivityLogExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
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
            'Action',
            'Description',
            'User',
            'IP Address',
            'Count',
            'Created At',
        ];
    }

    public function map($log): array
    {
        $user = $log->user;

        return [
            $log->id,
            $log->action ?? '-',
            $log->description ?? '-',
            $user?->name ?? $user?->email ?? '-',
            $log->ip_address ?? '-',
            (int) ($log->count ?? 1),
            $log->created_at?->format('Y-m-d H:i:s') ?? '-',
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
            'B' => 20,
            'C' => 40,
            'D' => 20,
            'E' => 15,
            'F' => 20,
            'G' => 15,
            'H' => 25,
            'I' => 30,
            'J' => 20,
        ];
    }
}
