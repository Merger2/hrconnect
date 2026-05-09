<?php

namespace App\Models;

use App\Enums\DayType;
use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['employee_id', 'leave_type_id', 'start_date', 'end_date', 'day_type', 'total_days', 'reason', 'proof_file', 'status'])]
class Leave extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'day_type' => DayType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'total_days' => 'decimal:2',
            'status' => RequestStatus::class,
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
}
