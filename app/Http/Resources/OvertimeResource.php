<?php

namespace App\Http\Resources;

use App\Models\Overtime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Overtime $resource
 *
 * @mixin Overtime
 */
class OvertimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'date' => $this->date->toDateString(),
            'start_time' => filled($this->start_time) ? Carbon::parse($this->start_time)->toIso8601String() : null,
            'end_time' => filled($this->end_time) ? Carbon::parse($this->end_time)->toIso8601String() : null,
            'total_hours' => (float) $this->total_hours,
            'description' => $this->description,
            'status' => $this->status->value,
            'amount' => $this->amount,
            // Subset minimal — query overtime memuat employee partial (id,employee_number,full_name).
            'employee' => $this->whenLoaded('employee', fn () => $this->employee ? [
                'id' => $this->employee->id,
                'employee_number' => $this->employee->employee_number,
                'full_name' => $this->employee->full_name,
            ] : null),
            'approvals' => ApprovalResource::collection($this->whenLoaded('approvals')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
