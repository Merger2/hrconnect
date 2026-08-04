<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\ActivityLogDetail;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class EmployeeObserver
{
    /**
     * Field yang diaudit (sensitive fields).
     */
    protected array $auditedFields = [
        'basic_salary',
    ];

    public function updated(Employee $employee): void
    {
        $dirty = $employee->getDirty();

        $changed = array_intersect(array_keys($dirty), $this->auditedFields);

        if ($changed === []) {
            return;
        }

        $actorId = Auth::id();
        $action = 'Sensitive Field Changed';

        $activityLog = ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'description' => 'Perubahan field sensitif pada Employee #'.$employee->id,
            'ip_address' => request()?->ip(),
        ]);

        foreach ($changed as $field) {
            $oldValue = $employee->getOriginal($field);
            $newValue = $employee->$field;

            ActivityLogDetail::create([
                'activity_log_id' => $activityLog->id,
                'entity_type' => Employee::class,
                'entity_id' => $employee->id,
                'field' => $field,
                'old_value' => ['value' => $oldValue],
                'new_value' => ['value' => $newValue],
                'integrity_hash' => hash_hmac('sha256', json_encode([
                    'activity_log_id' => $activityLog->id,
                    'entity_type' => Employee::class,
                    'entity_id' => $employee->id,
                    'field' => $field,
                    'old_value' => ['value' => $oldValue],
                    'new_value' => ['value' => $newValue],
                ], JSON_THROW_ON_ERROR), (string) config('app.key')),
            ]);
        }
    }
}
