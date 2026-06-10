<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'date' => $this->date?->toDateString(),
            'clock_in_time' => $this->clock_in_time?->toIso8601String(),
            'clock_out_time' => $this->clock_out_time?->toIso8601String(),
            'status' => $this->status?->value,
            'is_wfa' => $this->is_wfa,
            'late_minutes' => $this->late_minutes,
            'verification_method' => $this->verification_method,
            'employee' => EmployeeResource::make($this->whenLoaded('employee')),
        ];
    }
}
