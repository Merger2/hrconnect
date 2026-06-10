<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employee_number,
            'full_name' => $this->full_name,
            'status' => $this->status?->value,
            'employment_type' => $this->employment_type?->value,
            'position' => PositionResource::make($this->whenLoaded('position')),
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'branch' => BranchResource::make($this->whenLoaded('branch')),
        ];
    }
}
