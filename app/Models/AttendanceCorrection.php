<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PENDING_ADMIN = 'pending_admin';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const TYPE_MISSING_CHECK_IN = 'missing_check_in';

    public const TYPE_MISSING_CHECK_OUT = 'missing_check_out';

    public const TYPE_WRONG_SHIFT = 'wrong_shift';

    public const TYPE_WRONG_TIME = 'wrong_time';

    protected $table = 'attendance_corrections';

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'actual_clock_in',
        'actual_clock_out',
        'reason',
        'attachment',
        'status',
        'approved_by',
        'approved_at',
        'user_id',
        'attendance_id',
        'request_type',
        'requested_time_in',
        'requested_time_out',
        'requested_shift_id',
        'current_snapshot',
        'head_approved_by',
        'head_approved_at',
        'reviewed_by',
        'reviewed_at',
        'rejection_note',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'actual_clock_in' => 'datetime',
        'actual_clock_out' => 'datetime',
        'requested_time_in' => 'datetime',
        'requested_time_out' => 'datetime',
        'current_snapshot' => 'array',
        'head_approved_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function requestedShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'requested_shift_id');
    }

    public function headApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_approved_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => __('Pending Supervisor Review'),
            self::STATUS_PENDING_ADMIN => __('Pending Admin Review'),
            self::STATUS_APPROVED => __('Approved'),
            self::STATUS_REJECTED => __('Rejected'),
            default => $this->status ?? __('Unknown'),
        };
    }

    public function requestTypeLabel(): string
    {
        return self::TYPES[$this->request_type] ?? $this->request_type ?? __('N/A');
    }

    public static function requestTypes(): array
    {
        return self::TYPES;
    }

    public const TYPES = [
        self::TYPE_MISSING_CHECK_OUT => 'Missing Check Out',
        self::TYPE_WRONG_TIME => 'Wrong Time',
    ];
}
