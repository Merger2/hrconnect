<?php

namespace App\Http\Resources;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Asset $resource
 *
 * @mixin Asset
 */
class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'serial_number' => $this->serial_number,
            'code' => $this->code,
            'category' => $this->category,
            'is_available' => $this->is_available,
            'status' => $this->status->value,
            'handovers' => AssetHandoverResource::collection($this->whenLoaded('handovers')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
