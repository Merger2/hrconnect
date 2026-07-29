<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AttendanceComponentImport implements SkipsEmptyRows, ToCollection, WithHeadingRow, WithValidation
{
    public int $rowCount = 0;

    public array $errors = [];

    protected ?int $selectedShiftId = null;

    public function __construct(?int $selectedShiftId = null)
    {
        $this->selectedShiftId = $selectedShiftId;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            try {
                $employee = Employee::where('employee_number', $row['employee_number'])
                    ->orWhere('id', $row['employee_id'] ?? null)
                    ->first();

                if (! $employee) {
                    $this->errors[] = "Employee not found: {$row['employee_number']}";

                    continue;
                }

                $date = $this->parseDate($row['date']);
                if (! $date) {
                    $this->errors[] = "Invalid date format: {$row['date']}";

                    continue;
                }

                Attendance::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date' => $date,
                    ],
                    [
                        'clock_in' => $this->parseTime($row['clock_in'] ?? null, $date),
                        'clock_out' => $this->parseTime($row['clock_out'] ?? null, $date),
                        'status' => $row['status'] ?? 'present',
                        'shift_id' => $this->selectedShiftId,
                        'notes' => $row['notes'] ?? null,
                    ]
                );

                $this->rowCount++;
            } catch (\Throwable $e) {
                Log::error('AttendanceComponentImport row error', [
                    'row' => $row->toArray(),
                    'error' => $e->getMessage(),
                ]);
                $this->errors[] = $e->getMessage();
            }
        }
    }

    public function rules(): array
    {
        return [
            'employee_number' => 'required|string',
            'date' => 'required|date_format:Y-m-d',
            'clock_in' => 'nullable|date_format:H:i:s',
            'clock_out' => 'nullable|date_format:H:i:s',
            'status' => 'nullable|in:present,absent,late,leave,wfh',
        ];
    }

    private function parseDate($value): ?Carbon
    {
        if ($value instanceof \DateTime) {
            return Carbon::instance($value);
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function parseTime($value, Carbon $date): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \DateTime) {
            return Carbon::instance($value);
        }

        try {
            $time = Carbon::createFromFormat('H:i:s', $value);

            return $date->copy()->setTime($time->hour, $time->minute, $time->second);
        } catch (\Throwable) {
            return null;
        }
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
