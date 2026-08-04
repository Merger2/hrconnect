<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\ActivityLogDetail;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class RoleObserver
{
    /**
     * Field yang diaudit: kolom model => nama field pada audit detail.
     * permission_keys disimpan sebagai 'permissions' di activity log.
     */
    protected array $auditedFields = [
        'permission_keys' => 'permissions',
    ];

    public function updated(Role $role): void
    {
        $dirty = $role->getDirty();

        $changed = array_intersect_key($this->auditedFields, array_flip(array_keys($dirty)));

        if ($changed === []) {
            return;
        }

        $actorId = Auth::id();
        $action = 'Role Updated';

        $activityLog = ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'description' => 'Perubahan permission pada Role #'.$role->id,
            'ip_address' => request()?->ip(),
        ]);

        foreach ($changed as $field => $auditField) {
            $oldValue = $role->getOriginal($field);
            $newValue = $role->$field;

            ActivityLogDetail::create([
                'activity_log_id' => $activityLog->id,
                'entity_type' => Role::class,
                'entity_id' => $role->id,
                'field' => $auditField,
                'old_value' => ['value' => $oldValue],
                'new_value' => ['value' => $newValue],
                'integrity_hash' => hash_hmac('sha256', json_encode([
                    'activity_log_id' => $activityLog->id,
                    'entity_type' => Role::class,
                    'entity_id' => $role->id,
                    'field' => $auditField,
                    'old_value' => ['value' => $oldValue],
                    'new_value' => ['value' => $newValue],
                ], JSON_THROW_ON_ERROR), (string) config('app.key')),
            ]);
        }
    }
}
