<?php

namespace App\Http\Resources;

use App\Models\LoanInstallment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property LoanInstallment $resource
 *
 * @mixin LoanInstallment
 */
class LoanInstallmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'loan_id' => $this->loan_id,
            'installment_number' => $this->installment_number,
            'amount_paid' => (float) $this->amount_paid,
            'status' => $this->status->value,
            'due_date' => $this->due_date->toDateString(),
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
    }
}
