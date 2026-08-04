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
        'payslip_password',
    ];

    /**
     * Field yang nilainya diredaksi di audit detail (secrets/PII).
     * Disimpan sebagai {redacted: true} — nilai asli TIDAK pernah ditulis.
     */
    protected array $redactedFields = [
        'payslip_password',
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
            if (in_array($field, $this->redactedFields, true)) {
                $oldValue = ['redacted' => true];
                $newValue = ['redacted' => true];
            } else {
                $oldValue = ['value' => $employee->getOriginal($field)];
                $newValue = ['value' => $employee->$field];
            }

            ActivityLogDetail::create([
                'activity_log_id' => $activityLog->id,
                'entity_type' => Employee::class,
                'entity_id' => $employee->id,
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'integrity_hash' => hash_hmac('sha256', json_encode([
                    'activity_log_id' => $activityLog->id,
                    'entity_type' => Employee::class,
                    'entity_id' => $employee->id,
                    'field' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ], JSON_THROW_ON_ERROR), (string) config('app.key')),
            ]);
        }
    }
}
