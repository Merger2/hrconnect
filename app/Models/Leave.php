<?php

namespace App\Models;

use App\Enums\DayType;
use App\Enums\RequestStatus;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['employee_id', 'leave_type_id', 'start_date', 'end_date', 'day_type', 'total_days', 'reason', 'proof_file', 'status', 'rejection_reason'])]
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

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function calculateTotalDays(): float
    {
        // copy() mencegah variabel asli start_date dan end_date berubah
        $start = $this->start_date->copy();
        $end = $this->end_date->copy();
        $totalDays = 0.0;

        // Operator Ternary: Jika Full Day = 1 hari, jika Half Day = 0.5 hari
        $dayTypeMultiplier = $this->day_type === DayType::FULL_DAY ? 1.0 : 0.5;

        while ($start->lte($end)) {
            if (!$start->isWeekend() && !Holiday::isHoliday($start)) {
                $totalDays += $dayTypeMultiplier;
            }
            $start->addDay();
        }
        return $totalDays;
    }

    public function validateQuota(): bool
    {
        if (!$this->leaveType->deducts_from_quota) {
            return true;
        }

        // Jika memotong kuota (contoh: Cuti Tahunan), cari saldonya.
        $balance = LeaveBalance::where('employee_id', $this->employee_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->where('year', $this->start_date->year)
            ->first();
            
        if (!$balance) {
            return false;
        }

        $totalRequestedDays = $balance->used + $this->calculateTotalDays();
        $totalAvailableQuota = $balance->quota + $balance->carry_forward;
        
        return $totalRequestedDays <= $totalAvailableQuota;
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
