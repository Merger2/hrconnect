<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\PayrollStatus;
use App\Models\Payroll;
use App\Models\User;

/**
 * PayrollPolicy — authorization untuk Payroll model.
 *
 * Akses sangat sensitif (data PII gaji):
 * - Finance (process_payroll) → semua
 * - Super Admin → semua
 * - Employee → diri sendiri saja (view + download payslip)
 * - HR Manager TIDAK punya akses payroll (separation of duties)
 *
 * Lock policy: Published/Paid payroll TIDAK BISA di-edit/delete.
 */
class PayrollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::VIEW_PAYROLLS->value);
    }

    public function view(User $user, Payroll $payroll): bool
    {
        // Finance / Super Admin → all
        if ($user->hasRole(['super-admin', 'finance'])) {
            return true;
        }

        // Employee → diri sendiri saja
        return $user->employee?->id === $payroll->employee_id
            && $user->can(Permission::VIEW_PAYSLIP->value);
    }

    /**
     * Generate payroll — hanya Finance.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::PROCESS_PAYROLL->value);
    }

    /**
     * Update payroll — hanya Finance & status DRAFT (lock policy).
     */
    public function update(User $user, Payroll $payroll): bool
    {
        if (! $user->can(Permission::PROCESS_PAYROLL->value)) {
            return false;
        }

        return $payroll->status === PayrollStatus::DRAFT;
    }

    /**
     * Publish payroll → LOCKED PERMANEN (sesuai PRD §11.7).
     */
    public function publish(User $user, Payroll $payroll): bool
    {
        return $user->can(Permission::PROCESS_PAYROLL->value)
            && $payroll->status === PayrollStatus::DRAFT;
    }

    /**
     * Delete payroll — Finance only, hanya DRAFT.
     * Published/Paid payroll PERMANENT (koreksi via PayrollAdjustment).
     */
    public function delete(User $user, Payroll $payroll): bool
    {
        return $user->can(Permission::PROCESS_PAYROLL->value)
            && $payroll->status === PayrollStatus::DRAFT;
    }

    /**
     * Download e-payslip PDF (re-auth password confirmation di Livewire).
     */
    public function downloadPayslip(User $user, Payroll $payroll): bool
    {
        if (! $user->can(Permission::DOWNLOAD_PAYSLIP->value)) {
            return false;
        }

        // Finance → all
        if ($user->hasRole(['super-admin', 'finance'])) {
            return true;
        }

        // Employee → diri sendiri saja, dan payroll harus published/paid
        return $user->employee?->id === $payroll->employee_id
            && in_array($payroll->status, [PayrollStatus::PUBLISHED, PayrollStatus::PAID], true);
    }
}
