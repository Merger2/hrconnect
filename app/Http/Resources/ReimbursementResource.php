<?php

namespace App\Http\Resources;

use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Reimbursement $resource
 *
 * @mixin Reimbursement
 */
class ReimbursementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'category_id' => $this->category_id,
            'category' => ReimbursementCategoryResource::make($this->whenLoaded('category')),
            'title' => $this->title,
            'amount' => (int) $this->amount,
            'description' => $this->description,
            'expense_date' => $this->expense_date->toDateString(),
            'receipt_file' => $this->receipt_file,
            'status' => $this->status->value,
            'payroll_id' => $this->payroll_id,
            'approvals' => ApprovalResource::collection($this->whenLoaded('approvals')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
