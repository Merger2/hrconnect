<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperShiftSwapRequest
 */
class ShiftSwapRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'shift_swap_requests';

    protected $fillable = [
        'user_id',
        'schedule_id',
        'schedule_date',
        'requester_id',
        'target_id',
        'current_shift_id',
        'requested_shift_id',
        'replacement_user_id',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_note',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requester_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'target_id');
    }

    public function currentShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'current_shift_id');
    }

    public function requestedShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'requested_shift_id');
    }

    public function replacementUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replacement_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? __('Unknown');
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_APPROVED => __('Approved'),
            self::STATUS_REJECTED => __('Rejected'),
        ];
    }

    public function effectiveScheduleDate(): ?CarbonInterface
    {
        return $this->schedule?->date ?? $this->schedule_date;
    }
}
