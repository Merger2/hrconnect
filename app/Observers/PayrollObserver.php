<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\ActivityLogDetail;
use App\Models\Payroll;
use Illuminate\Support\Facades\Auth;

class PayrollObserver
{
    protected array $auditedFields = ['net_salary', 'status', 'basic_salary', 'gross_salary', 'total_allowance', 'overtime_pay', 'pph21', 'bpjs_health', 'bpjs_employment', 'loan_deduction', 'attendance_penalty', 'total_deduction'];

    public function updated(Payroll $payroll): void
    {
        $dirty = $payroll->getDirty();
        $changed = array_intersect(array_keys($dirty), $this->auditedFields);

        if ($changed === []) {
            return;
        }

        $actorId = Auth::id();
        $action = 'Payroll Updated';

        $activityLog = ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'description' => "Perubahan payroll pada Employee #{$payroll->employee_id}, Periode {$payroll->period}",
            'ip_address' => request()->ip(),
        ]);

        foreach ($changed as $field) {
            $oldValue = $payroll->getOriginal($field);
            $newValue = $payroll->$field;

            ActivityLogDetail::create([
                'activity_log_id' => $activityLog->id,
                'entity_type' => Payroll::class,
                'entity_id' => $payroll->id,
                'field' => $field,
                'old_value' => ['value' => $oldValue],
                'new_value' => ['value' => $newValue],
                'integrity_hash' => hash_hmac('sha256', json_encode([
                    'activity_log_id' => $activityLog->id,
                    'entity_type' => Payroll::class,
                    'entity_id' => $payroll->id,
                    'field' => $field,
                    'old_value' => ['value' => $oldValue],
                    'new_value' => ['value' => $newValue],
                ], JSON_THROW_ON_ERROR), (string) config('app.key')),
            ]);
        }
    }
}
