<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OvertimeRequestsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected User $user,
        protected array $filters,
    ) {}

    public function collection(): Collection
    {
        return collect($this->filters);
    }

    public function headings(): array
    {
        return [
            __('Employee'),
            __('Date'),
            __('Start Time'),
            __('End Time'),
            __('Duration'),
            __('Status'),
            __('Reason'),
        ];
    }

    public function map($row): array
    {
        return [
            $row['employee_name'] ?? '',
            $row['date'] ?? '',
            $row['start_time'] ?? '',
            $row['end_time'] ?? '',
            $row['duration'] ?? '',
            $row['status'] ?? '',
            $row['reason'] ?? '',
        ];
    }
}
