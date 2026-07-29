<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationAttendanceEvent extends Model
{
    use HasFactory, HasUlids;

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    public const EVENT_CHECK_IN = 'check_in';

    public const EVENT_CHECK_OUT = 'check_out';

    protected $fillable = [
        'integration_client_id',
        'source',
        'idempotency_key',
        'employee_code',
        'user_id',
        'attendance_id',
        'event_type',
        'occurred_at',
        'latitude',
        'longitude',
        'device_id',
        'status',
        'normalized_payload',
        'raw_payload',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'normalized_payload' => 'array',
            'raw_payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
