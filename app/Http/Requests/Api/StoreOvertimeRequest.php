<?php

namespace App\Http\Requests\Api;

use App\Enums\RequestStatus;
use App\Models\Overtime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Overtime request with daily (4h max) and weekly (18h max) validation per UU Cipta Kerja.
 *
 * - start_time and end_time must be in H:i format.
 * - Daily max 4 hours overtime (UU Cipta Kerja).
 * - Weekly max 18 hours across all approved overtime.
 * - Status defaults to PENDING; approval workflow is created after store.
 */
class StoreOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'description' => ['required', 'string', 'min:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal lembur wajib diisi.',
            'date.date' => 'Format tanggal lembur tidak valid.',
            'date.after_or_equal' => 'Tanggal lembur tidak boleh sebelum hari ini.',
            'start_time.required' => 'Jam mulai lembur wajib diisi.',
            'start_time.date_format' => 'Format jam mulai harus HH:MM (contoh: 16:00).',
            'end_time.required' => 'Jam selesai lembur wajib diisi.',
            'end_time.date_format' => 'Format jam selesai harus HH:MM (contoh: 20:00).',
            'description.required' => 'Deskripsi lembur wajib diisi.',
            'description.min' => 'Deskripsi lembur minimal 10 karakter.',
        ];
    }

    /**
     * Hook validasi setelah rules() dasar lolos.
     * Di sini kita validasi logika bisnis yang butuh query database.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $start = CarbonImmutable::parse($this->input('date').' '.$this->input('start_time'));
                $end = CarbonImmutable::parse($this->input('date').' '.$this->input('end_time'));

                if ($end->lessThanOrEqualTo($start)) {
                    $end = $end->addDay();
                }

                $hoursToday = $start->diffInMinutes($end) / 60;

                if ($hoursToday > 4) {
                    $validator->errors()->add(
                        'end_time',
                        'Maksimal lembur 4 jam per hari.'
                    );

                    return;
                }

                $date = CarbonImmutable::parse($this->input('date'));
                $weekStart = $date->copy()->startOfWeek();
                $weekEnd = $date->copy()->endOfWeek();

                $employee = auth()->user()->employee;

                if (! $employee) {
                    return;
                }

                $weeklyHours = Overtime::where(
                    'employee_id',
                    $employee->id
                )
                    ->whereBetween('date', [$weekStart, $weekEnd])
                    ->where('status', '!=', RequestStatus::REJECTED->value)
                    ->sum('total_hours');

                $weeklyHours += $hoursToday;

                if ($weeklyHours > 18) {
                    $validator->errors()->add(
                        'end_time',
                        "Maksimal lembur 18 jam per minggu. Minggu ini sudah {$weeklyHours} jam."
                    );
                }
            },
        ];
    }
}
