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
        static::updating(function (Reimbursement $reimbursement) {
            $originalRaw = $reimbursement->getRawOriginal('status');
            $newStatus = $reimbursement->status;

            // Allow APPROVED → PAID (forward transition via linkToPayroll)
            if ($originalRaw === ReimbursementStatus::APPROVED->value
                && $newStatus === ReimbursementStatus::PAID) {
                return;
            }

            // Allow APPROVED_L1 → APPROVED (via L2 approval)
            if ($originalRaw === ReimbursementStatus::APPROVED_L1->value
                && $newStatus === ReimbursementStatus::APPROVED) {
                return;
            }

            // Allow APPROVED_L1 → REJECTED (via rejection after L1 approved)
            if ($originalRaw === ReimbursementStatus::APPROVED_L1->value
                && $newStatus === ReimbursementStatus::REJECTED) {
                return;
            }

            if (in_array($originalRaw, [
                ReimbursementStatus::PAID->value,
                ReimbursementStatus::APPROVED->value,
                ReimbursementStatus::REJECTED->value,
            ], true)) {
                throw new BusinessRuleException(
                    'Reimbursement dengan status '.$originalRaw.' tidak dapat diubah.'
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
