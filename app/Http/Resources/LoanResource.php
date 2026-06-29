<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => EmployeeResource::make($this->whenLoaded('employee')),
            'amount' => (float) $this->amount,
            'interest_rate' => (float) $this->interest_rate,
            'tenor_months' => $this->tenor_months,
            'monthly_installment' => (float) $this->monthly_installment,
            'status' => $this->status?->value,
            'is_settled' => $this->is_settled,
            'rejection_reason' => $this->rejection_reason,
            'installments' => LoanInstallmentResource::collection($this->whenLoaded('installments')),
            'approvals' => ApprovalResource::collection($this->whenLoaded('approvals')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
