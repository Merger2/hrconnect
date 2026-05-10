<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['loan_id', 'payroll_id', 'amount_paid', 'installment_number', 'status', 'due_date', 'paid_at'])]
class LoanInstallment extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'due_date' => 'date',
            'installment_number' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
