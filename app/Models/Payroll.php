<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperPayroll
 */
#[Fillable(['employee_id', 'period', 'basic_salary', 'total_allowance', 'gross_salary', 'overtime_pay', 'pph21', 'bpjs_health', 'bpjs_employment', 'loan_deduction', 'attendance_penalty', 'total_deduction', 'net_salary', 'status', 'pdf_path', 'rejection_reason', 'payment_date', 'payment_method'])]
class Payroll extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::updating(function (Payroll $payroll) {
            $originalStatus = $payroll->getOriginal('status');

            $isDirtyStatus = $payroll->isDirty('status');
            $newStatus = $isDirtyStatus ? $payroll->status : null;

            if ($isDirtyStatus && $originalStatus instanceof PayrollStatus && $newStatus instanceof PayrollStatus) {
                if ($originalStatus->canTransitionTo($newStatus)) {
                    return;
                }

                throw new BusinessRuleException(
                    "Transisi status dari {$originalStatus->value} ke {$newStatus->value} tidak diizinkan."
                );
            }

            if ($originalStatus instanceof PayrollStatus
                && in_array($originalStatus, [PayrollStatus::APPROVED, PayrollStatus::PAID], true)) {
                throw new BusinessRuleException(
                    'Payroll dengan status '.$originalStatus->value.' tidak dapat diubah.'
                );
            }
        });
    }

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'total_allowance' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'pph21' => 'decimal:2',
            'bpjs_health' => 'decimal:2',
            'bpjs_employment' => 'decimal:2',
            'loan_deduction' => 'decimal:2',
            'attendance_penalty' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'payment_date' => 'date',
            'payment_method' => 'string',
            'status' => PayrollStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class);
    }

    public function loanInstallments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    public function isLocked(): bool
    {
        return in_array($this->status, [PayrollStatus::APPROVED, PayrollStatus::PAID], true);
    }

    public function isTerminal(): bool
    {
        return $this->status === PayrollStatus::PAID;
    }

    public function canTransitionTo(PayrollStatus $target): bool
    {
        return $this->status instanceof PayrollStatus && $this->status->canTransitionTo($target);
    }

    public function getAllowancesAttribute(): array
    {
        if (! $this->relationLoaded('items')) {
            $this->load('items');
        }

        return $this->items
            ->where('type', 'allowance')
            ->pluck('amount', 'name')
            ->toArray();
    }

    public function getDeductionsAttribute(): array
    {
        if (! $this->relationLoaded('items')) {
            $this->load('items');
        }

        return $this->items
            ->where('type', 'deduction')
            ->pluck('amount', 'name')
            ->toArray();
    }
}
