<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['employee_id', 'shift_id', 'date', 'clock_in', 'clock_out', 'lat_in', 'long_in', 'lat_out', 'long_out', 'clock_in_is_mocked', 'clock_in_accuracy', 'clock_out_is_mocked', 'clock_out_accuracy', 'device_fingerprint', 'face_similarity_score', 'status', 'is_wfa', 'photo_selfie_in', 'photo_selfie_out'])]
class Attendance extends Model
{
    use HasFactory, SoftDeletes;
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'lat_in' => 'decimal:8',
            'long_in' => 'decimal:8',
            'lat_out' => 'decimal:8',
            'long_out' => 'decimal:8',
            'clock_in_is_mocked' => 'boolean',
            'clock_in_accuracy' => 'decimal:2',
            'clock_out_is_mocked' => 'boolean',
            'clock_out_accuracy' => 'decimal:2',
            'face_similarity_score' => 'decimal:2',
            'is_wfa' => 'boolean',
            'status' => AttendanceStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function overtime(): HasOne
    {
        return $this->hasOne(Overtime::class);
    }

}
