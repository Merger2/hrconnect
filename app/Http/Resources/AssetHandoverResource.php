<?php

namespace App\Http\Resources;

use App\Models\AssetHandover;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property AssetHandover $resource
 *
 * @mixin AssetHandover
 */
class AssetHandoverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'employee_id' => $this->employee_id,
            'employee' => EmployeeResource::make($this->whenLoaded('employee')),
            'handover_date' => $this->handover_date->toDateString(),
            'return_date' => $this->return_date?->toDateString(),
            'condition' => $this->condition,
            'category' => $this->category->value,
        ];
    }
}
