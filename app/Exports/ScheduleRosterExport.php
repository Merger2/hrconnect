<?php

namespace App\Exports;

use App\Models\Schedule;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Roster export — M11 (AUDIT.md): pindah dari tabel legacy `shift_schedules`
 * ke `schedules` (single source of truth, 2026-08-06). Employee di-resolve
 * via user.employee; status Off untuk jadwal is_off.
 */
class ScheduleRosterExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    protected Authenticatable $user;

    protected array $filters;

    public function __construct(Authenticatable $user, array $filters)
    {
        $this->user = $user;
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $query = Schedule::query()
            ->with(['user.employee.division', 'user.employee.position', 'shift'])
            ->whereHas('user.employee', fn ($q) => $q->whereNull('resign_date'));

        if (! empty($this->filters['start_date'])) {
            $query->where('date', '>=', $this->filters['start_date']);
        }
        if (! empty($this->filters['end_date'])) {
            $query->where('date', '<=', $this->filters['end_date']);
        }
        if (! empty($this->filters['division'])) {
            $query->whereHas('user.employee', fn ($q) => $q->where('division_id', $this->filters['division']));
        }
        if (! empty($this->filters['shift_id'])) {
            $query->where('shift_id', $this->filters['shift_id']);
        }
        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
                    ->orWhereHas('user.employee', fn ($q) => $q->where('employee_number', 'ilike', "%{$search}%"));
            });
        }

        return $query->orderBy('date')->orderBy('user_id')->get();
    }

    public function headings(): array
    {
        return [
            'Employee Number',
            'Employee Name',
            'Department',
            'Position',
            'Date',
            'Shift',
            'Start Time',
            'End Time',
            'Status',
        ];
    }

    public function map($schedule): array
    {
        $employee = $schedule->user?->employee;
        $shift = $schedule->is_off ? null : $schedule->shift;

        return [
            $employee->employee_number ?? '-',
            $schedule->user->name ?? $employee->full_name ?? '-',
            $employee?->division->name ?? '-',
            $employee?->position->name ?? '-',
            $schedule->date?->format('Y-m-d') ?? '-',
            $shift->name ?? '-',
            $shift->start_time ?? '-',
            $shift->end_time ?? '-',
            $schedule->is_off ? 'Off' : ucfirst($schedule->status ?? 'scheduled'),
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
            'F' => 15,
            'G' => 12,
            'H' => 12,
            'I' => 15,
        ];
    }
}
