<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeaveRequestsExport implements FromCollection, WithHeadings, WithMapping
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
            __('Leave Type'),
            __('Start Date'),
            __('End Date'),
            __('Status'),
            __('Reason'),
        ];
    }

    public function map($row): array
    {
        return [
            $row['employee_name'] ?? '',
            $row['leave_type'] ?? '',
            $row['start_date'] ?? '',
            $row['end_date'] ?? '',
            $row['status'] ?? '',
            $row['reason'] ?? '',
        ];
    }
}
