<?php

namespace App\Models;

use App\Enums\OvertimeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['employee_id', 'attendance_id', 'date', 'total_hours', 'calculated_pay', 'status'])]
class Overtime extends Model
{
    use HasFactory, SoftDeletes;
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_hours' => 'decimal:2',
            'calculated_pay' => 'decimal:2',
            'status' => OvertimeStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
