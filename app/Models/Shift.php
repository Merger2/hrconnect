<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'start_time', 'late_tolerance_minutes', 'end_time', 'is_active'])]
class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'start_time' => 'time',
            'end_time' => 'time',
            'late_tolerance_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected function duration(): Attribute
    {
        return Attribute::make(
            get: function () {
                $start = Carbon::parse($this->attributes['start_time']);
                $end = Carbon::parse($this->attributes['end_time']);

                if ($end->lessThan($start)) {
                    $end->addDay();
                }

                return $start->diffInHours($end);
            }
        );
    }

    public function calculateLateMinutes(Carbon $clockIn): int
    {
        $shiftStart = Carbon::parse($this->start_time);
        $maxArrivalTime = $shiftStart->copy()->addMinutes($this->late_tolerance_minutes);
        if ($clockIn->lessThanOrEqualTo($maxArrivalTime)) {
            return 0;
        }

        return (int) $maxArrivalTime->diffInMinutes($clockIn);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function shiftSchedules(): HasMany
    {
        return $this->hasMany(ShiftSchedule::class);
    }
}
