<?php

namespace App\Http\Resources;

use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property LeaveType $resource
 *
 * @mixin LeaveType
 */
class LeaveTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'quota' => $this->quota,
            'is_paid' => $this->is_paid,
        ];
    }
}
