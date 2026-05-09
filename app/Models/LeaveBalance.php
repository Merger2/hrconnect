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
            'quota' => 'integer',
            'used' => 'integer',
            'carry_forward' => 'integer',
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

    public function available(): int
    {
        return $this->quota + $this->carry_forward - $this->used;
    }
}
