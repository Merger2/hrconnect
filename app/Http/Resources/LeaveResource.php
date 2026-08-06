<?php

namespace App\Http\Resources;

use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Leave $resource
 *
 * @mixin Leave
 */
class LeaveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'leave_type' => LeaveTypeResource::make($this->whenLoaded('leaveType')),
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'day_type' => $this->day_type->value,
            'total_days' => (float) $this->total_days,
            'reason' => $this->reason,
            'proof_file' => $this->proof_file,
            'status' => $this->status->value,
            'approvals' => ApprovalResource::collection($this->whenLoaded('approvals')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
