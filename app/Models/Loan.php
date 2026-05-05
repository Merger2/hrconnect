<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['employee_id', 'rejection_reason', 'created_by', 'amount', 'tenor_months', 'monthly_installment', 'status', 'is_settled'])]
class Loan extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'monthly_installment' => 'decimal:2',
            'tenor_months' => 'integer',
            'is_settled' => 'boolean',
            'status' => LoanStatus::class,
        ];
    }
    
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }
}
