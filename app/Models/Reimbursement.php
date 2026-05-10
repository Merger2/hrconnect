<?php

namespace App\Models;

use App\Enums\ReimbursementStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['employee_id', 'payroll_id', 'category_id', 'title', 'expense_date', 'amount', 'description', 'receipt_file', 'attachment_path', 'status', 'rejection_reason'])]
class Reimbursement extends Model
{
    use HasFactory, SoftDeletes;
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

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }
}
