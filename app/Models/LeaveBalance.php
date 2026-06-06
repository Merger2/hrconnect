<?php

namespace App\Models;

use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperLeaveBalance
 */
#[Fillable(['employee_id', 'leave_type_id', 'year', 'quota', 'used', 'carry_forward', 'carry_forward_deadline'])]
class LeaveBalance extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quota' => 'decimal:1',
            'used' => 'decimal:1',
            'carry_forward' => 'decimal:1',
            'carry_forward_deadline' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function available(): float
    {
        $carryForward = 0;
        if ($this->carry_forward_deadline && today()->lessThanOrEqualTo($this->carry_forward_deadline)) {
            $carryForward = $this->carry_forward;
        }

        return $this->quota + $carryForward - $this->used;
    }

    public function hasEnoughQuota(float $daysNeeded): bool
    {
        return $this->available() >= $daysNeeded;
    }

    public function deduct(float $days): void
    {
        if ($this->available() < $days) {
            throw new BusinessRuleException("Saldo cuti tidak mencukupi. Sisa: {$this->available()} hari, diminta: {$days} hari.");
        }
        $this->increment('used', $days);
    }

    public function refund(float $days): void
    {
        $newUsed = max(0, (float) $this->used - $days);
        $this->update(['used' => $newUsed]);
    }
}
