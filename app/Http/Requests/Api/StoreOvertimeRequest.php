<?php

namespace App\Http\Requests\Api;

use App\Enums\RequestStatus;
use App\Models\Overtime;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOvertimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->employee !== null;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'description' => ['nullable', 'string', 'min:10'],
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
                $start = Carbon::parse($this->input('start_time'));
                $end = Carbon::parse($this->input('end_time'));
                $hoursToday = $start->diffInMinutes($end) / 60;

                if ($hoursToday > 4) {
                    $validator->errors()->add(
                        'end_time',
                        'Maksimal lembur 4 jam per hari.'
                    );

                    return;
                }

                $date = Carbon::parse($this->input('date'));
                $weekStart = $date->copy()->startOfWeek();
                $weekEnd = $date->copy()->endOfWeek();

                $weeklyHours = Overtime::where(
                    'employee_id',
                    auth()->user()->employee->id
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
