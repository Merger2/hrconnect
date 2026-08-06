<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Attendance $resource
 *
 * @mixin Attendance
 */
class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'date' => $this->date->toDateString(),
            'clock_in_time' => $this->time_in?->toIso8601String(),
            'clock_out_time' => $this->time_out?->toIso8601String(),
            'status' => $this->status->value,
            'is_wfa' => $this->is_wfa,
            'late_minutes' => $this->late_minutes,
            'verification_method' => $this->verification_method,
            'employee' => EmployeeResource::make($this->whenLoaded('employee')),
        ];
    }
}
