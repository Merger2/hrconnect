<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperAttendanceOfflineSubmission
 */
class AttendanceOfflineSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'clock_in',
        'clock_out',
        'latitude',
        'longitude',
        'photo_path',
        'status',
        'sync_error',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
