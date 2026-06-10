<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'approvable_type' => $this->approvable_type,
            'approvable_id' => $this->approvable_id,
            'approver' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver->id,
                'full_name' => $this->approver->full_name,
            ]),
            'level' => $this->level,
            'status' => $this->status?->value,
            'notes' => $this->notes,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
