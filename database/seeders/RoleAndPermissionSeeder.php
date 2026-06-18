<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * RoleAndPermissionSeeder — 5 roles × 44 permissions sesuai SRS §3.2.2.
 *
 * Idempotent: aman dijalankan berkali-kali (firstOrCreate + syncPermissions).
 *
 * 5 Roles:
 * - super-admin : all 44 permissions (executive override)
 * - hr-manager  : view all + manage HR + approve L2 leaves/OT + KB
 * - finance     : payroll + tax/BPJS + approve L2 reimbursement
 * - manager     : approve L1 + view team data
 * - employee    : view diri sendiri + dashboard
 *
 * Default guard: 'web' (sesuai Spatie Permission default).
 *
 * Verifikasi: setelah seed, super-admin harus punya 44 permission,
 * employee minimal punya `view_dashboard`.
 */
class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles & permissions Spatie agar perubahan langsung terlihat
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Step 1: Create semua 44 permission dari enum
        foreach (PermissionEnum::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        // Step 2: Buat & sinkronkan permission per role
        $this->syncRole('super-admin', $this->superAdminPermissions());
        $this->syncRole('hr-manager', $this->hrManagerPermissions());
        $this->syncRole('finance', $this->financePermissions());
        $this->syncRole('manager', $this->managerPermissions());
        $this->syncRole('employee', $this->employeePermissions());
    }

    /**
     * Buat role kalau belum ada, lalu sync permission-nya.
     *
     * @param  array<int, PermissionEnum>  $permissions
     */
    private function syncRole(string $name, array $permissions): Role
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        $role->syncPermissions(
            array_map(fn (PermissionEnum $p) => $p->value, $permissions)
        );

        return $role;
    }

    /**
     * Super Admin: semua 44 permission.
     */
    private function superAdminPermissions(): array
    {
        return PermissionEnum::cases();
    }

    /**
     * HR Manager: view all data + manage employee + approve L2 + KB management.
     * NO: process_payroll, manage_tax/bpjs (Finance), manage_companies (Super).
     */
    private function hrManagerPermissions(): array
    {
        return [
            PermissionEnum::VIEW_DASHBOARD,
            // View master data (read-only)
            PermissionEnum::VIEW_BRANCHES,
            PermissionEnum::VIEW_DEPARTMENTS,
            PermissionEnum::VIEW_POSITIONS,
            // Employee management
            PermissionEnum::VIEW_EMPLOYEES,
            PermissionEnum::MANAGE_EMPLOYEES,
            // Attendance
            PermissionEnum::VIEW_ATTENDANCES,
            PermissionEnum::MANAGE_ATTENDANCES,
            // Leave
            PermissionEnum::VIEW_LEAVES,
            PermissionEnum::APPROVE_LEAVES_L2,
            // Overtime
            PermissionEnum::VIEW_OVERTIMES,
            PermissionEnum::APPROVE_OVERTIMES_L2,
            // Reimbursement (view only — Finance yang approve L2)
            PermissionEnum::VIEW_REIMBURSEMENTS,
            // WFA
            PermissionEnum::VIEW_WFA_PENDING,
            // Loan/Asset (view + manage assets)
            PermissionEnum::VIEW_LOANS,
            PermissionEnum::VIEW_ASSETS,
            PermissionEnum::MANAGE_ASSETS,
            // Audit
            PermissionEnum::VIEW_ACTIVITY_LOGS,
            PermissionEnum::VIEW_AUDIT_LOGS,
            // KnowledgeBase
            PermissionEnum::VIEW_KNOWLEDGEBASE,
            PermissionEnum::MANAGE_KNOWLEDGEBASE,
        ];
    }

    /**
     * Finance: payroll processing + tax/BPJS configs + approve L2 reimbursement.
     * View employee untuk konteks payroll.
     */
    private function financePermissions(): array
    {
        return [
            PermissionEnum::VIEW_DASHBOARD,
            // View context
            PermissionEnum::VIEW_EMPLOYEES,
            // Reimbursement management + L2 approval (Finance)
            PermissionEnum::VIEW_REIMBURSEMENTS,
            PermissionEnum::MANAGE_REIMBURSEMENTS,
            PermissionEnum::APPROVE_REIMBURSEMENTS_L2,
            // Loan management
            PermissionEnum::VIEW_LOANS,
            PermissionEnum::MANAGE_LOANS,
            // Payroll core
            PermissionEnum::VIEW_PAYSLIP,
            PermissionEnum::DOWNLOAD_PAYSLIP,
            PermissionEnum::PROCESS_PAYROLL,
            PermissionEnum::VIEW_PAYROLLS,
            PermissionEnum::MANAGE_TAX_CONFIGS,
            PermissionEnum::MANAGE_BPJS_CONFIGS,
        ];
    }

    /**
     * Manager: L1 approval + view tim (filtered di Policy via parent_id).
     * Manager OTOMATIS dapat semua permission Employee (multi-role inheritance via UI).
     */
    private function managerPermissions(): array
    {
        return [
            PermissionEnum::VIEW_DASHBOARD,
            PermissionEnum::VIEW_EMPLOYEES,
            // L1 Approvals (Manager)
            PermissionEnum::VIEW_ATTENDANCES,
            PermissionEnum::VIEW_LEAVES,
            PermissionEnum::APPROVE_LEAVES_L1,
            PermissionEnum::VIEW_OVERTIMES,
            PermissionEnum::APPROVE_OVERTIMES_L1,
            PermissionEnum::VIEW_REIMBURSEMENTS,
            PermissionEnum::APPROVE_REIMBURSEMENTS_L1,
            // WFA approval (Manager only)
            PermissionEnum::APPROVE_WFA,
            PermissionEnum::VIEW_WFA_PENDING,
        ];
    }

    /**
     * Employee: ESS — view data sendiri + dashboard.
     * Filter "self only" di-handle oleh Policy class (ownership check).
     */
    private function employeePermissions(): array
    {
        return [
            PermissionEnum::VIEW_DASHBOARD,
            PermissionEnum::VIEW_ATTENDANCES,
            PermissionEnum::VIEW_LEAVES,
            PermissionEnum::VIEW_OVERTIMES,
            PermissionEnum::VIEW_REIMBURSEMENTS,
            PermissionEnum::VIEW_LOANS,
            PermissionEnum::VIEW_ASSETS,
            PermissionEnum::VIEW_PAYSLIP,
            PermissionEnum::DOWNLOAD_PAYSLIP,
            PermissionEnum::VIEW_PAYROLLS,
        ];
    }
}
