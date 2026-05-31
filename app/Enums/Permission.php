<?php

namespace App\Enums;

/**
 * Permission enum — 44 cases sesuai SRS §3.2.2 Permission Matrix.
 *
 * Format: `{action}_{module}` snake_case (REQ-MNT-02 naming convention).
 * Pakai backed enum string supaya kompatibel dengan Spatie Permission
 * (yang menyimpan permission name sebagai string di database).
 *
 * Konvensi:
 * - `view_*`     → akses read (sering paired dengan ownership/team filter di Policy)
 * - `manage_*`   → CRUD penuh (HR/Admin level)
 * - `approve_*`  → approval workflow (L1 = Manager, L2 = HR/Finance)
 * - `process_*`  → operasi khusus (mis. process_payroll oleh Finance)
 * - `download_*` → export/download dengan re-auth password
 *
 * Mapping ke 5 role di RoleAndPermissionSeeder:
 * - super-admin : semua 44 permission (executive override)
 * - hr-manager  : view all + manage employees/HR + approve L2 leaves/OT + KB
 * - finance     : process payroll + approve L2 reimbursement + view payslip + tax/bpjs
 * - manager     : approve L1 + view team data
 * - employee    : view own data + dashboard
 *
 * Pakai `Permission::VIEW_DASHBOARD->value` saat call `$user->can(...)` atau
 * di Policy class, bukan string mentah, untuk type safety.
 */
enum Permission: string
{
    // ── Dashboard (1) ────────────────────────────────────────────────
    case VIEW_DASHBOARD = 'view_dashboard';

    // ── Master Data — Company (2) ────────────────────────────────────
    case VIEW_COMPANIES = 'view_companies';
    case MANAGE_COMPANIES = 'manage_companies';

    // ── Master Data — Branch (2) ─────────────────────────────────────
    case VIEW_BRANCHES = 'view_branches';
    case MANAGE_BRANCHES = 'manage_branches';

    // ── Master Data — Department (2) ─────────────────────────────────
    case VIEW_DEPARTMENTS = 'view_departments';
    case MANAGE_DEPARTMENTS = 'manage_departments';

    // ── Master Data — Position (2) ───────────────────────────────────
    case VIEW_POSITIONS = 'view_positions';
    case MANAGE_POSITIONS = 'manage_positions';

    // ── Employee (2) ─────────────────────────────────────────────────
    case VIEW_EMPLOYEES = 'view_employees';
    case MANAGE_EMPLOYEES = 'manage_employees';

    // ── Attendance (2) ───────────────────────────────────────────────
    case VIEW_ATTENDANCES = 'view_attendances';
    case MANAGE_ATTENDANCES = 'manage_attendances';

    // ── Leave (3) ────────────────────────────────────────────────────
    case VIEW_LEAVES = 'view_leaves';
    case APPROVE_LEAVES_L1 = 'approve_leaves_l1';
    case APPROVE_LEAVES_L2 = 'approve_leaves_l2';

    // ── Overtime (3) ─────────────────────────────────────────────────
    case VIEW_OVERTIMES = 'view_overtimes';
    case APPROVE_OVERTIMES_L1 = 'approve_overtimes_l1';
    case APPROVE_OVERTIMES_L2 = 'approve_overtimes_l2';

    // ── Reimbursement (4) ────────────────────────────────────────────
    case VIEW_REIMBURSEMENTS = 'view_reimbursements';
    case MANAGE_REIMBURSEMENTS = 'manage_reimbursements';
    case APPROVE_REIMBURSEMENTS_L1 = 'approve_reimbursements_l1';
    case APPROVE_REIMBURSEMENTS_L2 = 'approve_reimbursements_l2';

    // ── WFA (2) ──────────────────────────────────────────────────────
    case APPROVE_WFA = 'approve_wfa';
    case VIEW_WFA_PENDING = 'view_wfa_pending';

    // ── Loan / Kasbon (V2 deferred, enum tetap siap) (2) ─────────────
    case VIEW_LOANS = 'view_loans';
    case MANAGE_LOANS = 'manage_loans';

    // ── Asset (V2 deferred, enum tetap siap) (2) ─────────────────────
    case VIEW_ASSETS = 'view_assets';
    case MANAGE_ASSETS = 'manage_assets';

    // ── Payroll (6) ──────────────────────────────────────────────────
    case VIEW_PAYSLIP = 'view_payslip';
    case DOWNLOAD_PAYSLIP = 'download_payslip';
    case PROCESS_PAYROLL = 'process_payroll';
    case VIEW_PAYROLLS = 'view_payrolls';
    case MANAGE_TAX_CONFIGS = 'manage_tax_configs';
    case MANAGE_BPJS_CONFIGS = 'manage_bpjs_configs';

    // ── Audit & Logs (2) ─────────────────────────────────────────────
    case VIEW_ACTIVITY_LOGS = 'view_activity_logs';
    case VIEW_AUDIT_LOGS = 'view_audit_logs';

    // ── Settings & Roles (5) ─────────────────────────────────────────
    case MANAGE_SETTINGS = 'manage_settings';
    case MANAGE_COMPANY_SETTINGS = 'manage_company_settings';
    case MANAGE_ROLES = 'manage_roles';
    case MANAGE_HOLIDAYS = 'manage_holidays';
    case MANAGE_SHIFTS = 'manage_shifts';

    // ── KnowledgeBase (2) ────────────────────────────────────────────
    case MANAGE_KNOWLEDGEBASE = 'manage_knowledgebase';
    case VIEW_KNOWLEDGEBASE = 'view_knowledgebase';

    /**
     * Total permission count — gunakan ini untuk verifikasi seeder integrity.
     */
    public static function count(): int
    {
        return count(self::cases());
    }

    /**
     * Group permission menjadi array per modul untuk display di UI manage roles.
     */
    public static function grouped(): array
    {
        return [
            'Dashboard' => [self::VIEW_DASHBOARD],
            'Company' => [self::VIEW_COMPANIES, self::MANAGE_COMPANIES],
            'Branch' => [self::VIEW_BRANCHES, self::MANAGE_BRANCHES],
            'Department' => [self::VIEW_DEPARTMENTS, self::MANAGE_DEPARTMENTS],
            'Position' => [self::VIEW_POSITIONS, self::MANAGE_POSITIONS],
            'Employee' => [self::VIEW_EMPLOYEES, self::MANAGE_EMPLOYEES],
            'Attendance' => [self::VIEW_ATTENDANCES, self::MANAGE_ATTENDANCES],
            'Leave' => [
                self::VIEW_LEAVES,
                self::APPROVE_LEAVES_L1,
                self::APPROVE_LEAVES_L2,
            ],
            'Overtime' => [
                self::VIEW_OVERTIMES,
                self::APPROVE_OVERTIMES_L1,
                self::APPROVE_OVERTIMES_L2,
            ],
            'Reimbursement' => [
                self::VIEW_REIMBURSEMENTS,
                self::MANAGE_REIMBURSEMENTS,
                self::APPROVE_REIMBURSEMENTS_L1,
                self::APPROVE_REIMBURSEMENTS_L2,
            ],
            'WFA' => [self::APPROVE_WFA, self::VIEW_WFA_PENDING],
            'Loan' => [self::VIEW_LOANS, self::MANAGE_LOANS],
            'Asset' => [self::VIEW_ASSETS, self::MANAGE_ASSETS],
            'Payroll' => [
                self::VIEW_PAYSLIP,
                self::DOWNLOAD_PAYSLIP,
                self::PROCESS_PAYROLL,
                self::VIEW_PAYROLLS,
                self::MANAGE_TAX_CONFIGS,
                self::MANAGE_BPJS_CONFIGS,
            ],
            'Audit' => [self::VIEW_ACTIVITY_LOGS, self::VIEW_AUDIT_LOGS],
            'Settings' => [
                self::MANAGE_SETTINGS,
                self::MANAGE_COMPANY_SETTINGS,
                self::MANAGE_ROLES,
                self::MANAGE_HOLIDAYS,
                self::MANAGE_SHIFTS,
            ],
            'KnowledgeBase' => [
                self::MANAGE_KNOWLEDGEBASE,
                self::VIEW_KNOWLEDGEBASE,
            ],
        ];
    }
}
