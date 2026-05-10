<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        if ($this->carry_forward_deadline && now()->lessThanOrEqualTo($this->carry_forward_deadline)) {
            $carryForward = $this->carry_forward;
        }
        return $this->quota + $carryForward - $this->used;
    }

    public function deduct(float $days): void
    {
        $this->used += $days;
        $this->save();
    }
}
