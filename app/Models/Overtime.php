<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Traits\Approvable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperOvertime
 */
#[Fillable(['employee_id', 'attendance_id', 'date', 'start_time', 'end_time', 'description', 'total_hours', 'amount', 'rejection_reason', 'status'])]
class Overtime extends Model
{
    use Approvable, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'total_hours' => 'decimal:2',
            'amount' => 'decimal:2',
            'status' => RequestStatus::class,
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

    public function durationHours(): float
    {
        if (! $this->start_time || ! $this->end_time) {
            return 0.0;
        }

        $end = $this->end_time;

        // B-11: Detect overnight shift (end < start) → add 1 day
        if ($this->end_time->lessThan($this->start_time)) {
            $end = $this->end_time->copy()->addDay();
        }

        // diffInMinutes / 60 mencegah pembulatan ke bawah yang merugikan uang karyawan
        $minutes = $this->start_time->diffInMinutes($end);

        return round($minutes / 60, 2);
    }
}
