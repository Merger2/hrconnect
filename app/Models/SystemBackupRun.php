<?php

namespace App\Models;

use App\Support\BackupSecurityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemBackupRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'status',
        'requested_by_user_id',
        'queue',
        'file_disk',
        'file_path',
        'file_name',
        'size_bytes',
        'error_message',
        'meta',
        'started_at',
        'completed_at',
        'failed_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'meta' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SystemBackupRun $backupRun): void {
            $service = app(BackupSecurityService::class);

            $user = User::query()->find($backupRun->requested_by_user_id);

            if (! $user) {
                throw new AuthorizationException('You do not have permission to manage the backup system.');
            }

            $service->assertCanManage($user);
            $service->auditQueued($backupRun);
        });

        static::updating(function (SystemBackupRun $backupRun): void {
            if (! $backupRun->isDirty('status')) {
                return;
            }

            $service = app(BackupSecurityService::class);

            $wasCompleted = $backupRun->status === 'completed';

            $service->enforceSizeLimit($backupRun);

            if ($wasCompleted && $backupRun->status === 'failed') {
                $service->auditFailed($backupRun);
            }
        });
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
