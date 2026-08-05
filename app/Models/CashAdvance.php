<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperCashAdvance
 */
class CashAdvance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'purpose',
        'status',
        'approval_matrix_rule_id',
        'approval_steps',
        'approval_current_step',
        'approval_completed_steps',
        'payment_month',
        'payment_year',
        'approved_by',
        'approved_at',
        'head_approved_by',
        'head_approved_at',
        'finance_approved_by',
        'finance_approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approval_steps' => 'array',
        'approval_completed_steps' => 'array',
        'approved_at' => 'datetime',
        'head_approved_at' => 'datetime',
        'finance_approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function headApprover()
    {
        return $this->belongsTo(User::class, 'head_approved_by');
    }

    public function financeApprover()
    {
        return $this->belongsTo(User::class, 'finance_approved_by');
    }
}
