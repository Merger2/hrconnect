<?php

namespace App\Models;

use App\Enums\DayType;
use App\Enums\RequestStatus;
use App\Traits\Approvable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperLeave
 */
#[Fillable(['employee_id', 'leave_type_id', 'start_date', 'end_date', 'day_type', 'total_days', 'reason', 'proof_file', 'status', 'rejection_reason'])]
class Leave extends Model
{
    use Approvable, HasFactory, SoftDeletes;

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

    public function calculateTotalDays(): float
    {
        if (! $this->start_date || ! $this->end_date) {
            return 0.0;
        }
        // copy() mencegah variabel asli start_date dan end_date berubah
        $start = $this->start_date->copy();
        $end = $this->end_date->copy();
        $totalDays = 0.0;

        // Operator Ternary: Jika Full Day = 1 hari, jika Half Day = 0.5 hari
        $dayTypeMultiplier = $this->day_type->weight();

        while ($start->lte($end)) {
            if (! $start->isWeekend() && ! Holiday::isHoliday($start)) {
                $totalDays += $dayTypeMultiplier;
            }
            $start = $start->addDay();
        }

        return $totalDays;
    }

    public function validateQuota(): bool
    {
        if (! $this->leaveType?->deducts_from_quota || ! $this->start_date) {
            return true;
        }

        // Jika memotong kuota (contoh: Cuti Tahunan), cari saldonya.
        $balance = LeaveBalance::where('employee_id', $this->employee_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->where('year', $this->start_date->year)
            ->first();

        if (! $balance) {
            return false;
        }

        return $balance->hasEnoughQuota($this->calculateTotalDays());
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public static function hasOverlap(int $employeeId, CarbonInterface $start, CarbonInterface $end): bool
    {
        $activeStatuses = [
            RequestStatus::PENDING->value,
            RequestStatus::APPROVED_L1->value,
            RequestStatus::APPROVED->value,
        ];

        return self::where('employee_id', $employeeId)
            ->whereIn('status', $activeStatuses)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            })
            ->exists();
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
