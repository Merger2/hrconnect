<?php

namespace App\Models;

use App\Enums\ReimbursementStatus;
use App\Exceptions\BusinessRuleException;
use App\Traits\Approvable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperReimbursement
 */
#[Fillable(['employee_id', 'payroll_id', 'category_id', 'title', 'expense_date', 'amount', 'description', 'receipt_file', 'attachment_path', 'status', 'rejection_reason'])]
class Reimbursement extends Model
{
    use Approvable, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        // B-19: Block terminal state changes — PAID/APPROVED/REJECTED cannot revert
        static::updating(function (Reimbursement $reimbursement) {
            $originalStatus = $reimbursement->getOriginal('status');

            $terminalStates = [
                ReimbursementStatus::PAID->value,
                ReimbursementStatus::APPROVED->value,
                ReimbursementStatus::REJECTED->value,
            ];

            if (in_array($originalStatus, $terminalStates)) {
                throw new BusinessRuleException(
                    'Reimbursement dengan status '.$originalStatus.' tidak dapat diubah.'
                );
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'status' => ReimbursementStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReimbursementCategory::class, 'category_id');
    }

    public function isApproved(): bool
    {
        return $this->status === ReimbursementStatus::APPROVED;
    }
}
