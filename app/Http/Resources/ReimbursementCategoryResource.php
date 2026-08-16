<?php

namespace App\Http\Resources;

use App\Models\ReimbursementCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ReimbursementCategory $resource
 *
 * @mixin ReimbursementCategory
 */
class ReimbursementCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'is_active' => $this->is_active,
        ];
    }
}
