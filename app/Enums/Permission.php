<?php

namespace App\Enums;

/**
 * Permission enum — 66 cases sesuai SRS §§3.2.2 Permission Matrix.
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
 * Mapping ke 5 role di RoleAndPermissionSeeder (selaras struktur PT DCMS):
 * - super-admin : semua permission (executive override) — Owner & IT Support
 * - admin       : full HR + admin panel + system settings + RBAC + payroll — HRD
 * - finance     : process payroll + approve L2 reimbursement + view payslip + tax/bpjs
 * - manager     : approve L1 + view team data
 * - employee    : view own data + dashboard
 *
 * Pakai `Permission::VIEW_DASHBOARD->value` saat call `$user->can(...)` atau
 * di Policy class, bukan string mentah, untuk type safety.
 */
enum Permission: string
{
    // – Dashboard (1) –
    case VIEW_DASHBOARD = 'view_dashboard';

    // – Document Requests (1) — admin panel visibility –
    case VIEW_ADMIN_DOCUMENT_REQUESTS = 'view_admin_document_requests';

    // – Master Data (10) –
    case VIEW_COMPANIES = 'view_companies';
    case MANAGE_COMPANIES = 'manage_companies';
    case VIEW_BRANCHES = 'view_branches';
    case MANAGE_BRANCHES = 'manage_branches';
    case VIEW_DIVISIONS = 'view_divisions';
    case MANAGE_DIVISIONS = 'manage_divisions';
    case VIEW_POSITIONS = 'view_positions';
    case MANAGE_JOB_TITLES = 'manage_job_titles';
    case MANAGE_EDUCATIONS = 'manage_educations';
    case MANAGE_SHIFTS = 'manage_shifts';
    case MANAGE_LEAVE_TYPES = 'manage_leave_types';
    case MANAGE_LEAVE_ENTITLEMENTS = 'manage_leave_entitlements';
    case VIEW_ADMIN_ACCOUNTS = 'view_admin_accounts';

    // – Employee (2) –
    case VIEW_EMPLOYEES = 'view_employees';
    case MANAGE_EMPLOYEES = 'manage_employees';

    // – Attendance (2) –
    case VIEW_ATTENDANCES = 'view_attendances';
    case MANAGE_ATTENDANCES = 'manage_attendances';

    // – Attendance Correction (1) –
    case MANAGE_SCHEDULES = 'manage_schedules';
    case MANAGE_HOLIDAYS = 'manage_holidays';

    // – Shift Swap (1) –
    case MANAGE_SHIFT_SWAP_APPROVALS = 'manage_shift_swap_approvals';

    // – Leave (4) –
    case VIEW_LEAVES = 'view_leaves';
    case MANAGE_LEAVE_APPROVALS = 'manage_leave_approvals';
    case APPROVE_LEAVES_L1 = 'approve_leaves_l1';
    case APPROVE_LEAVES_L2 = 'approve_leaves_l2';

    // – Overtime (4) –
    case VIEW_OVERTIMES = 'view_overtimes';
    case MANAGE_OVERTIME = 'manage_overtime';
    case APPROVE_OVERTIMES_L1 = 'approve_overtimes_l1';
    case APPROVE_OVERTIMES_L2 = 'approve_overtimes_l2';

    // – Reimbursement (4) –
    case VIEW_REIMBURSEMENTS = 'view_reimbursements';
    case MANAGE_REIMBURSEMENTS = 'manage_reimbursements';
    case APPROVE_REIMBURSEMENTS_L1 = 'approve_reimbursements_l1';
    case APPROVE_REIMBURSEMENTS_L2 = 'approve_reimbursements_l2';

    // – WFA (2) –
    case APPROVE_WFA = 'approve_wfa';
    case VIEW_WFA_PENDING = 'view_wfa_pending';

    // – Loan / Kasbon (V2 deferred, enum tetap siap) (2) –
    case VIEW_LOANS = 'view_loans';
    case MANAGE_LOANS = 'manage_loans';

    // – Asset (V2 deferred, enum tetap siap) (2) –
    case VIEW_ASSETS = 'view_assets';
    case MANAGE_ASSETS = 'manage_assets';

    // – Announcement (1) –
    case MANAGE_ANNOUNCEMENTS = 'manage_announcements';

    // – HR Checklists (2) –
    case VIEW_HR_CHECKLISTS = 'view_hr_checklists';
    case MANAGE_HR_CHECKLISTS = 'manage_hr_checklists';

    // – Operations / CRM (5) –
    case VIEW_OPERATIONS_WORKSPACE = 'view_operations_workspace';
    case VIEW_COLLABORATION_WORKSPACE = 'view_collaboration_workspace';
    case VIEW_CUSTOM_FORMS = 'view_custom_forms';
    case MANAGE_OPERATIONS_WORKSPACE = 'manage_operations_workspace';
    case MANAGE_COLLABORATION_WORKSPACE = 'manage_collaboration_workspace';

    // – Payroll (7) –
    case VIEW_PAYSLIP = 'view_payslip';
    case DOWNLOAD_PAYSLIP = 'download_payslip';
    case PROCESS_PAYROLL = 'process_payroll';
    case VIEW_PAYROLLS = 'view_payrolls';
    case MANAGE_TAX_CONFIGS = 'manage_tax_configs';
    case MANAGE_BPJS_CONFIGS = 'manage_bpjs_configs';

    // – Reports (1) –
    case VIEW_OPERATIONAL_REPORTS = 'view_operational_reports';

    // – Audit & Logs (2) –
    case VIEW_ACTIVITY_LOGS = 'view_activity_logs';
    case VIEW_AUDIT_LOGS = 'view_audit_logs';
    case MANAGE_USER_SESSIONS = 'manage_user_sessions';

    // – System Settings (2) –
    case VIEW_ADMIN_SETTINGS = 'view_admin_settings';
    case MANAGE_SYSTEM_SETTINGS = 'manage_system_settings';
    case MANAGE_ENTERPRISE_LICENSE = 'manage_enterprise_license';

    // – Integrations (1) –
    case MANAGE_API_INTEGRATIONS = 'manage_api_integrations';

    // – Access & Authorization (3) –
    case ACCESS_ADMIN_PANEL = 'accessAdminPanel';
    case VIEW_ADMIN_DASHBOARD = 'view_admin_dashboard';
    case MANAGE_RBAC = 'manage_rbac';

    // – Notifications (2) –
    case VIEW_NOTIFICATIONS = 'view_notifications';
    case MANAGE_ADMIN_NOTIFICATIONS = 'manage_admin_notifications';

    // – KnowledgeBase (2) –
    case MANAGE_KNOWLEDGEBASE = 'manage_knowledgebase';
    case VIEW_KNOWLEDGEBASE = 'view_knowledgebase';

    // – Employee & User Management (4) –
    case MANAGE_USER_RECORD = 'manage_user_record';
    case MANAGE_EMPLOYEE_STATUSES = 'manage_employee_statuses';
    case APPROVE_EMPLOYEE_ACCOUNT_DELETION = 'approve_employee_account_deletion';
    case REVIEW_SUBORDINATE_REQUESTS = 'review_subordinate_requests';

    // – Master Data (1) –
    case MANAGE_MASTER_DATA = 'manage_master_data';

    // – Attendance Extensions (3) –
    case MANAGE_ATTENDANCE_CORRECTIONS = 'manage_attendance_corrections';
    case VIEW_ATTENDANCE_REPORTS = 'view_attendance_reports';
    case VIEW_ATTENDANCE_IMPORT_EXPORT = 'view_attendance_import_export';

    // – Appraisal (1) –
    case VIEW_ADMIN_APPRAISALS = 'view_admin_appraisals';

    // – WFH (1) –
    case MANAGE_WFH_REQUESTS = 'manage_wfh_requests';

    // – Cash Advance (1) –
    case MANAGE_CASH_ADVANCES = 'manage_cash_advances';

    // – Custom Forms (1) –
    case MANAGE_CUSTOM_FORMS = 'manage_custom_forms';

    // – System & Settings (3) –
    case MANAGE_SYSTEM_MAINTENANCE = 'manage_system_maintenance';
    case MANAGE_PAYROLL_SETTINGS = 'manage_payroll_settings';
    case MANAGE_KPI_SETTINGS = 'manage_kpi_settings';

    // – RBAC (1) –
    case ASSIGN_ROLES = 'assign_roles';

    // – Analytics (1) –
    case VIEW_ANALYTICS_DASHBOARD = 'view_analytics_dashboard';

    // – Import/Export (6) –
    case VIEW_USER_IMPORT_EXPORT = 'view_user_import_export';
    case EXPORT_ACTIVITY_LOGS = 'export_activity_logs';
    case EXPORT_ADMIN_REPORTS = 'export_admin_reports';
    case EXPORT_ATTENDANCES = 'export_attendances';
    case EXPORT_USERS = 'export_users';
    case IMPORT_ATTENDANCES = 'import_attendances';
    case IMPORT_USERS = 'import_users';

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
            'Document Requests' => [self::VIEW_ADMIN_DOCUMENT_REQUESTS],
            'Dashboard' => [self::VIEW_DASHBOARD],
            'Company' => [self::VIEW_COMPANIES, self::MANAGE_COMPANIES],
            'Branch' => [self::VIEW_BRANCHES, self::MANAGE_BRANCHES],
            'Division' => [self::VIEW_DIVISIONS, self::MANAGE_DIVISIONS],
            'Position' => [self::VIEW_POSITIONS],
            'Job Title' => [self::MANAGE_JOB_TITLES],
            'Education' => [self::MANAGE_EDUCATIONS],
            'Shift' => [self::MANAGE_SHIFTS],
            'Leave Type' => [self::MANAGE_LEAVE_TYPES],
            'Leave Entitlement' => [self::MANAGE_LEAVE_ENTITLEMENTS],
            'Admin Account' => [self::VIEW_ADMIN_ACCOUNTS],
            'Employee' => [self::VIEW_EMPLOYEES, self::MANAGE_EMPLOYEES, self::MANAGE_USER_RECORD, self::MANAGE_EMPLOYEE_STATUSES, self::APPROVE_EMPLOYEE_ACCOUNT_DELETION, self::REVIEW_SUBORDINATE_REQUESTS],
            'Master Data' => [self::MANAGE_MASTER_DATA],
            'Attendance' => [self::VIEW_ATTENDANCES, self::MANAGE_ATTENDANCES, self::MANAGE_ATTENDANCE_CORRECTIONS, self::VIEW_ATTENDANCE_REPORTS, self::VIEW_ATTENDANCE_IMPORT_EXPORT],
            'Schedule' => [self::MANAGE_SCHEDULES],
            'Holiday' => [self::MANAGE_HOLIDAYS],
            'Shift Swap' => [self::MANAGE_SHIFT_SWAP_APPROVALS],
            'Leave' => [
                self::VIEW_LEAVES,
                self::MANAGE_LEAVE_APPROVALS,
                self::APPROVE_LEAVES_L1,
                self::APPROVE_LEAVES_L2,
            ],
            'Overtime' => [
                self::VIEW_OVERTIMES,
                self::MANAGE_OVERTIME,
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
            'Announcement' => [self::MANAGE_ANNOUNCEMENTS],
            'HR Checklist' => [self::VIEW_HR_CHECKLISTS, self::MANAGE_HR_CHECKLISTS],
            'Operations' => [
                self::VIEW_OPERATIONS_WORKSPACE,
                self::VIEW_COLLABORATION_WORKSPACE,
                self::VIEW_CUSTOM_FORMS,
            ],
            'Payroll' => [
                self::VIEW_PAYSLIP,
                self::DOWNLOAD_PAYSLIP,
                self::PROCESS_PAYROLL,
                self::VIEW_PAYROLLS,
                self::MANAGE_TAX_CONFIGS,
                self::MANAGE_BPJS_CONFIGS,
                self::MANAGE_PAYROLL_SETTINGS,
                self::MANAGE_CASH_ADVANCES,
            ],
            'Reports' => [self::VIEW_OPERATIONAL_REPORTS, self::EXPORT_ATTENDANCES, self::EXPORT_USERS, self::IMPORT_ATTENDANCES, self::IMPORT_USERS, self::EXPORT_ADMIN_REPORTS, self::EXPORT_ACTIVITY_LOGS, self::VIEW_ATTENDANCE_REPORTS, self::VIEW_USER_IMPORT_EXPORT, self::VIEW_ATTENDANCE_IMPORT_EXPORT],
            'Audit' => [self::VIEW_ACTIVITY_LOGS, self::VIEW_AUDIT_LOGS],
            'User Session' => [self::MANAGE_USER_SESSIONS],
            'Settings' => [self::VIEW_ADMIN_SETTINGS, self::MANAGE_SYSTEM_SETTINGS, self::MANAGE_ENTERPRISE_LICENSE, self::MANAGE_SYSTEM_MAINTENANCE, self::MANAGE_KPI_SETTINGS, self::MANAGE_CUSTOM_FORMS],
            'Integration' => [self::MANAGE_API_INTEGRATIONS],
            'RBAC' => [self::MANAGE_RBAC, self::ASSIGN_ROLES],
            'Notifications' => [self::MANAGE_ADMIN_NOTIFICATIONS],
            'KnowledgeBase' => [
                self::MANAGE_KNOWLEDGEBASE,
                self::VIEW_KNOWLEDGEBASE,
            ],
        ];
    }
}
