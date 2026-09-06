<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Traits\Approvable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @mixin IdeHelperOvertime
 */
#[Fillable(['employee_id', 'attendance_id', 'date', 'start_time', 'end_time', 'description', 'total_hours', 'amount', 'insentif', 'approved_by', 'approved_at', 'notes', 'rejection_reason', 'status'])]
class Overtime extends Model
{
    /**
     * Blade/service lama membaca $overtime->reason (kolom sebenarnya:
     * description). Accessor ini menjaga kompatibilitas.
     */
    public function getReasonAttribute(): ?string
    {
        return $this->description;
    }

    use Approvable, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'string',
            'end_time' => 'string',
            'total_hours' => 'decimal:2',
            'amount' => 'decimal:2',
            'status' => RequestStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Employee::class,
            'id',
            'id',
            'employee_id',
            'user_id',
        );
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

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        // B-11: Detect overnight shift (end < start) → add 1 day
        if ($end->lessThan($start)) {
            $end = $end->copy()->addDay();
        }

        // diffInMinutes / 60 mencegah pembulatan ke bawah yang merugikan uang karyawan
        $minutes = $start->diffInMinutes($end);

        return round($minutes / 60, 2);
    }
}
