<?php

namespace App\Http\Resources;

use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Loan $resource
 *
 * @mixin Loan
 */
class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            // Hanya subset minimal — EmployeeResource penuh butuh kolom lengkap
            // (gender/status/dll), sementara query loan memuat employee partial.
            'employee' => $this->whenLoaded('employee', fn () => $this->employee ? [
                'id' => $this->employee->id,
                'employee_number' => $this->employee->employee_number,
                'full_name' => $this->employee->full_name,
            ] : null),
            'amount' => (float) $this->amount,
            'interest_rate' => (float) $this->interest_rate,
            'tenor_months' => $this->tenor_months,
            'monthly_installment' => (float) $this->monthly_installment,
            'status' => $this->status->value,
            'is_settled' => $this->is_settled,
            'rejection_reason' => $this->rejection_reason,
            'installments' => LoanInstallmentResource::collection($this->whenLoaded('installments')),
            'approvals' => ApprovalResource::collection($this->whenLoaded('approvals')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
