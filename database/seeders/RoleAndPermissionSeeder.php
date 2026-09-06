<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * RoleAndPermissionSeeder — 5 roles × 94 permissions (jumlah case PermissionEnum).
 *
 * Idempotent: aman dijalankan berkali-kali (firstOrCreate + syncPermissions).
 *
 * 5 Roles (selaras struktur PT DCMS):
 * - super-admin : semua permission (executive override) — Owner & IT Support
 * - admin       : full HR + admin panel + system settings + RBAC + payroll — HRD
 * - finance     : payroll + tax/BPJS + approve L2 reimbursement — staf Finance
 * - manager     : approve L1 + view team data + shift swap + overtime + HR checklists
 * - employee    : view diri sendiri + dashboard
 *
 * Default guard: 'web' (sesuai Spatie Permission default).
 *
 * Verifikasi: setelah seed, super-admin harus punya 94 permission,
 * employee minimal punya `view_dashboard`.
 *
 * ⚠️ Migrasi dari role hr-manager (sebelum 2026-08-05): baris role `hr-manager`
 * lama di DB produksi TIDAK dihapus otomatis (firstOrCreate). Reassign user lama
 * yang ber-role hr-manager ke `admin`, lalu hapus role hr-manager dari tabel roles
 * (manual/sekali jalan) supaya RBAC UI bersih.
 */
class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles & permissions Spatie agar perubahan langsung terlihat
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Step 1: Create semua 66 permission dari enum
        foreach (PermissionEnum::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        // Step 2: Buat & sinkronkan permission per role
        // Role aktual di DB: super-admin, admin (HRD), finance, manager, employee
        $this->syncRole('super-admin', $this->superAdminPermissions());
        $this->syncRole('finance', $this->financePermissions());
        $this->syncRole('admin', $this->adminPermissions());
        $this->syncRole('manager', $this->managerPermissions());
        $this->syncRole('employee', $this->employeePermissions());

        // Step 3: Flag super-admin role — is_super_admin = true
        \App\Models\Role::whereName('super-admin')->update(['is_super_admin' => true]);
    }

    /**
     * Buat role kalau belum ada, lalu sync permission-nya.
     *
     * @param  array<int, PermissionEnum>  $permissions
     */
    private function syncRole(string $name, array $permissions): Role
    {
        $slug = str($name)->lower()->toString();

        $role = Role::query()
            ->where('guard_name', 'web')
            ->where(fn ($query) => $query->where('name', $name)->orWhere('slug', $slug))
            ->first() ?? new Role(['guard_name' => 'web']);

        $role->name = $name;
        $role->slug = $slug;
        $role->save();

        $role->syncPermissions(
            array_map(fn (PermissionEnum $p) => $p->value, $permissions)
        );

        // Also update the permission_keys JSON column used by HasRolePermissions trait.
        // This is the ACTUAL permission check mechanism — NOT the Spatie pivot table.
        $role->permission_keys = array_map(fn (PermissionEnum $p) => $p->value, $permissions);
        $role->save();

        return $role;
    }

    /**
     * Super Admin: semua 67 permission.
     * PermissionEnum::cases() auto-include semua case baru.
     */
    private function superAdminPermissions(): array
    {
        return PermissionEnum::cases();
    }

    /**
     * Admin: full HR + admin panel access (non-superadmin) — dipakai HRD.
     */
    private function adminPermissions(): array
    {
        return [
            // Admin panel access
            PermissionEnum::ACCESS_ADMIN_PANEL,
            PermissionEnum::VIEW_ADMIN_DASHBOARD,
            PermissionEnum::VIEW_DASHBOARD,
            PermissionEnum::VIEW_ADMIN_DOCUMENT_REQUESTS,
            // Master data (full CRUD)
            PermissionEnum::VIEW_BRANCHES,
            PermissionEnum::VIEW_DIVISIONS,
            PermissionEnum::MANAGE_DIVISIONS,
            PermissionEnum::VIEW_POSITIONS,
            PermissionEnum::MANAGE_JOB_TITLES,
            PermissionEnum::MANAGE_EDUCATIONS,
            PermissionEnum::MANAGE_SHIFTS,
            PermissionEnum::MANAGE_LEAVE_TYPES,
            PermissionEnum::MANAGE_LEAVE_ENTITLEMENTS,
            PermissionEnum::VIEW_ADMIN_ACCOUNTS,
            PermissionEnum::VIEW_COMPANIES,
            PermissionEnum::MANAGE_COMPANIES,
            // Employee management
            PermissionEnum::VIEW_EMPLOYEES,
            PermissionEnum::MANAGE_EMPLOYEES,
            // Attendance
            PermissionEnum::VIEW_ATTENDANCES,
            PermissionEnum::MANAGE_ATTENDANCES,
            PermissionEnum::MANAGE_SCHEDULES,
            PermissionEnum::MANAGE_HOLIDAYS,
            PermissionEnum::MANAGE_ATTENDANCE_CORRECTIONS,
            PermissionEnum::MANAGE_SHIFT_SWAP_APPROVALS,
            // Leave
            PermissionEnum::VIEW_LEAVES,
            PermissionEnum::MANAGE_LEAVE_APPROVALS,
            PermissionEnum::APPROVE_LEAVES_L2,
            // Overtime
            PermissionEnum::VIEW_OVERTIMES,
            PermissionEnum::MANAGE_OVERTIME,
            PermissionEnum::APPROVE_OVERTIMES_L2,
            // Reimbursement
            PermissionEnum::VIEW_REIMBURSEMENTS,
            PermissionEnum::APPROVE_WFA,
            PermissionEnum::VIEW_WFA_PENDING,
            // Payroll (keputusan: HRD sebagai admin ikut proses payroll)
            PermissionEnum::VIEW_PAYSLIP,
            PermissionEnum::DOWNLOAD_PAYSLIP,
            PermissionEnum::PROCESS_PAYROLL,
            PermissionEnum::VIEW_PAYROLLS,
            PermissionEnum::MANAGE_TAX_CONFIGS,
            PermissionEnum::MANAGE_BPJS_CONFIGS,
            // Loan/Asset
            PermissionEnum::VIEW_LOANS,
            PermissionEnum::MANAGE_LOANS,
            PermissionEnum::MANAGE_CASH_ADVANCES,
            PermissionEnum::VIEW_ASSETS,
            PermissionEnum::MANAGE_ASSETS,
            // Announcement + HR Checklists
            PermissionEnum::MANAGE_ANNOUNCEMENTS,
            PermissionEnum::VIEW_HR_CHECKLISTS,
            PermissionEnum::MANAGE_HR_CHECKLISTS,
            // Operations
            PermissionEnum::VIEW_OPERATIONS_WORKSPACE,
            PermissionEnum::VIEW_COLLABORATION_WORKSPACE,
            PermissionEnum::VIEW_CUSTOM_FORMS,
            // Reports
            PermissionEnum::VIEW_OPERATIONAL_REPORTS,
            // Settings (full system access)
            PermissionEnum::VIEW_ADMIN_SETTINGS,
            PermissionEnum::MANAGE_SYSTEM_SETTINGS,
            // System management
            PermissionEnum::MANAGE_USER_SESSIONS,
            PermissionEnum::MANAGE_API_INTEGRATIONS,
            PermissionEnum::MANAGE_RBAC,
            // Notifications
            PermissionEnum::MANAGE_ADMIN_NOTIFICATIONS,
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
     * View employee untuk konteks payroll. Dipakai staf Finance PT DCMS.
     */
    private function financePermissions(): array
    {
        return [
            PermissionEnum::VIEW_DASHBOARD,
            PermissionEnum::ACCESS_ADMIN_PANEL,
            // View context
            PermissionEnum::VIEW_EMPLOYEES,
            PermissionEnum::VIEW_ATTENDANCES,
            // Reimbursement management + L2 approval (Finance)
            PermissionEnum::VIEW_REIMBURSEMENTS,
            PermissionEnum::MANAGE_REIMBURSEMENTS,
            PermissionEnum::APPROVE_REIMBURSEMENTS_L2,
            // Loan management
            PermissionEnum::VIEW_LOANS,
            PermissionEnum::MANAGE_LOANS,
            // Company info
            PermissionEnum::VIEW_COMPANIES,
            // Reports
            PermissionEnum::VIEW_OPERATIONAL_REPORTS,
            // Settings
            PermissionEnum::VIEW_ADMIN_SETTINGS,
            // Payroll core
            PermissionEnum::VIEW_PAYSLIP,
            PermissionEnum::DOWNLOAD_PAYSLIP,
            PermissionEnum::PROCESS_PAYROLL,
            PermissionEnum::VIEW_PAYROLLS,
            PermissionEnum::MANAGE_TAX_CONFIGS,
            PermissionEnum::MANAGE_BPJS_CONFIGS,
            PermissionEnum::MANAGE_PAYROLL_SETTINGS,
        ];
    }

    /**
     * Manager: L1 approval + view tim (filtered di Policy via parent_id).
     * NO view_branches, view_departments, view_positions — those are master data
     * view permissions for HR/Super Admin only. Manager sees team data through
     * policy filtering (parent_id), not master data view perms.
     */
    private function managerPermissions(): array
    {
        return [
            PermissionEnum::VIEW_DASHBOARD,
            PermissionEnum::VIEW_EMPLOYEES,
            PermissionEnum::VIEW_KNOWLEDGEBASE,
            PermissionEnum::VIEW_HR_CHECKLISTS,
            // Reports
            PermissionEnum::VIEW_OPERATIONAL_REPORTS,
            // L1 Approvals (Manager)
            PermissionEnum::VIEW_ATTENDANCES,
            PermissionEnum::VIEW_LEAVES,
            PermissionEnum::MANAGE_LEAVE_APPROVALS,
            PermissionEnum::APPROVE_LEAVES_L1,
            PermissionEnum::VIEW_OVERTIMES,
            PermissionEnum::MANAGE_OVERTIME,
            PermissionEnum::APPROVE_OVERTIMES_L1,
            PermissionEnum::MANAGE_SHIFT_SWAP_APPROVALS,
            PermissionEnum::VIEW_REIMBURSEMENTS,
            PermissionEnum::APPROVE_REIMBURSEMENTS_L1,
            // WFA approval (Manager only)
            PermissionEnum::APPROVE_WFA,
            PermissionEnum::VIEW_WFA_PENDING,
            // Team approval access (enables /approvals page)
            PermissionEnum::REVIEW_SUBORDINATE_REQUESTS,
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
            PermissionEnum::VIEW_KNOWLEDGEBASE,
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
