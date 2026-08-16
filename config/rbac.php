<?php

return [
    'sections' => [
        'overview' => [
            'label' => 'Overview',
            'description' => 'Dashboards and global announcements.',
        ],
        'master_data' => [
            'label' => 'Master Data',
            'description' => 'Companies, branches, divisions and reference data.',
        ],
        'hr' => [
            'label' => 'Human Resources',
            'description' => 'Employees, HR checklists and document requests.',
        ],
        'attendance' => [
            'label' => 'Attendance & Time',
            'description' => 'Attendance, schedules, holidays and flexible work.',
        ],
        'time_off' => [
            'label' => 'Time Off',
            'description' => 'Leaves and overtime.',
        ],
        'finance' => [
            'label' => 'Finance & Payroll',
            'description' => 'Payroll, reimbursements, loans, cash advances and assets.',
        ],
        'operations' => [
            'label' => 'Operations',
            'description' => 'Operations and collaboration workspaces.',
        ],
        'knowledge' => [
            'label' => 'Knowledge',
            'description' => 'Knowledge base and custom forms.',
        ],
        'reports' => [
            'label' => 'Reports & Import',
            'description' => 'Operational reports, exports and imports.',
        ],
        'audit' => [
            'label' => 'Audit & Security',
            'description' => 'Activity logs, audit logs and user sessions.',
        ],
        'access' => [
            'label' => 'Access Control',
            'description' => 'Roles, permissions and admin panel access.',
        ],
        'system' => [
            'label' => 'System & Settings',
            'description' => 'System settings, integrations and notifications.',
        ],
    ],

    'modules' => [
        // ── Overview ──────────────────────────────────────────────────────
        'dashboard' => [
            'label' => 'Dashboard',
            'section' => 'overview',
            'enterprise' => false,
            'description' => 'User and admin dashboards.',
            'actions' => [
                // Covers both `view_dashboard` and `view_admin_dashboard`
                // (both resolve to `admin.dashboard.view`).
                'view' => ['label' => 'View', 'permission' => 'admin.dashboard.view'],
            ],
        ],
        'analytics_dashboard' => [
            'label' => 'Analytics Dashboard',
            'section' => 'overview',
            'enterprise' => true,
            'description' => 'Analytics and KPI dashboards.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.analytics_dashboard.view'],
            ],
        ],
        'announcements' => [
            'label' => 'Announcements',
            'section' => 'overview',
            'enterprise' => false,
            'description' => 'Company announcements.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.announcements.manage'],
            ],
        ],

        // ── Master Data ───────────────────────────────────────────────────
        'companies' => [
            'label' => 'Companies',
            'section' => 'master_data',
            'enterprise' => true,
            'description' => 'Company records.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.companies.view'],
                'manage' => ['label' => 'Manage', 'permission' => 'admin.companies.manage'],
            ],
        ],
        'branches' => [
            'label' => 'Branches',
            'section' => 'master_data',
            'enterprise' => true,
            'description' => 'Branch records.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.branches.view'],
                'manage' => ['label' => 'Manage', 'permission' => 'admin.branches.manage'],
            ],
        ],
        'divisions' => [
            'label' => 'Divisions',
            'section' => 'master_data',
            'enterprise' => false,
            'description' => 'Division records.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.divisions.view'],
                'manage' => ['label' => 'Manage', 'permission' => 'admin.divisions.manage'],
            ],
        ],
        'positions' => [
            'label' => 'Positions',
            'section' => 'master_data',
            'enterprise' => false,
            'description' => 'Position records.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.positions.view'],
            ],
        ],
        'job_titles' => [
            'label' => 'Job Titles',
            'section' => 'master_data',
            'enterprise' => false,
            'description' => 'Job title records.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.job_titles.manage'],
            ],
        ],
        'educations' => [
            'label' => 'Educations',
            'section' => 'master_data',
            'enterprise' => false,
            'description' => 'Education level records.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.educations.manage'],
            ],
        ],
        'shifts' => [
            'label' => 'Shifts',
            'section' => 'master_data',
            'enterprise' => false,
            'description' => 'Shift records.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.shifts.manage'],
            ],
        ],
        'leave_types' => [
            'label' => 'Leave Types',
            'section' => 'master_data',
            'enterprise' => false,
            'description' => 'Leave type records.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.leave_types.manage'],
            ],
        ],
        'leave_entitlements' => [
            'label' => 'Leave Entitlements',
            'section' => 'master_data',
            'enterprise' => false,
            'description' => 'Leave entitlement records.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.leave_entitlements.manage'],
            ],
        ],
        'admin_accounts' => [
            'label' => 'Admin Accounts',
            'section' => 'master_data',
            'enterprise' => false,
            'description' => 'Admin account records.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.accounts.view'],
            ],
        ],
        'master_data' => [
            'label' => 'Master Data',
            'section' => 'master_data',
            'enterprise' => false,
            'description' => 'General master data management.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.master_data.manage'],
            ],
        ],

        // ── Human Resources ──────────────────────────────────────────────
        'employees' => [
            'label' => 'Employees',
            'section' => 'hr',
            'enterprise' => false,
            'description' => 'Employee master data and records.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.employees.view'],
                'manage' => ['label' => 'Manage', 'permission' => 'admin.employees.manage'],
                'manage_record' => ['label' => 'Manage User Record', 'permission' => 'admin.user_record.manage'],
                'manage_statuses' => ['label' => 'Manage Statuses', 'permission' => 'admin.employee_statuses.manage'],
                'approve_deletion' => ['label' => 'Approve Deletion', 'permission' => 'admin.employee_account_deletion.approve'],
                'review_requests' => ['label' => 'Review Requests', 'permission' => 'admin.subordinate_requests.review'],
            ],
        ],
        'hr_checklists' => [
            'label' => 'HR Checklists',
            'section' => 'hr',
            'enterprise' => false,
            'description' => 'HR onboarding and offboarding checklists.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.hr_checklists.view'],
                'manage' => ['label' => 'Manage', 'permission' => 'admin.hr_checklists.manage'],
            ],
        ],
        'document_requests' => [
            'label' => 'Document Requests',
            'section' => 'hr',
            'enterprise' => false,
            'description' => 'Employee document requests.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.document_requests.view'],
            ],
        ],

        // ── Attendance & Time ────────────────────────────────────────────
        'attendances' => [
            'label' => 'Attendance',
            'section' => 'attendance',
            'enterprise' => false,
            'description' => 'Attendance records, corrections, reports and import/export.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.attendances.view'],
                'manage' => ['label' => 'Manage', 'permission' => 'admin.attendances.manage'],
                'manage_corrections' => ['label' => 'Manage Corrections', 'permission' => 'admin.attendance_corrections.manage'],
                'view_reports' => ['label' => 'View Reports', 'permission' => 'admin.attendance_reports.view'],
                'view_import_export' => ['label' => 'View Import/Export', 'permission' => 'admin.attendance_import_export.view'],
                'export' => ['label' => 'Export', 'permission' => 'admin.attendances.export'],
                'import' => ['label' => 'Import', 'permission' => 'admin.attendances.import'],
            ],
        ],
        'schedules' => [
            'label' => 'Schedules',
            'section' => 'attendance',
            'enterprise' => false,
            'description' => 'Work schedules.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.schedules.manage'],
            ],
        ],
        'holidays' => [
            'label' => 'Holidays',
            'section' => 'attendance',
            'enterprise' => false,
            'description' => 'Holiday calendar.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.holidays.manage'],
            ],
        ],
        'shift_swap_approvals' => [
            'label' => 'Shift Swap Approvals',
            'section' => 'attendance',
            'enterprise' => false,
            'description' => 'Shift swap request approvals.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.shift_swap_approvals.manage'],
            ],
        ],
        'wfh_requests' => [
            'label' => 'WFH Requests',
            'section' => 'attendance',
            'enterprise' => false,
            'description' => 'Work from home request management.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.wfh_requests.manage'],
            ],
        ],
        'wfa' => [
            'label' => 'WFA',
            'section' => 'attendance',
            'enterprise' => true,
            'description' => 'Work from anywhere approvals.',
            'actions' => [
                'approve' => ['label' => 'Approve', 'permission' => 'admin.wfa.approve'],
                'view_pending' => ['label' => 'View Pending', 'permission' => 'admin.wfa_pending.view'],
            ],
        ],

        // ── Time Off ─────────────────────────────────────────────────────
        'leaves' => [
            'label' => 'Leave',
            'section' => 'time_off',
            'enterprise' => false,
            'description' => 'Leave requests and approvals.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.leaves.view'],
                'manage' => ['label' => 'Manage Approvals', 'permission' => 'admin.leave_approvals.manage'],
                'approve_l1' => ['label' => 'Approve Level 1', 'permission' => 'admin.leaves_l1.approve'],
                'approve_l2' => ['label' => 'Approve Level 2', 'permission' => 'admin.leaves_l2.approve'],
            ],
        ],
        'overtimes' => [
            'label' => 'Overtime',
            'section' => 'time_off',
            'enterprise' => false,
            'description' => 'Overtime requests and approvals.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.overtimes.view'],
                'manage' => ['label' => 'Manage', 'permission' => 'admin.overtime.manage'],
                'approve_l1' => ['label' => 'Approve Level 1', 'permission' => 'admin.overtimes_l1.approve'],
                'approve_l2' => ['label' => 'Approve Level 2', 'permission' => 'admin.overtimes_l2.approve'],
            ],
        ],

        // ── Finance & Payroll ────────────────────────────────────────────
        'reimbursements' => [
            'label' => 'Reimbursement',
            'section' => 'finance',
            'enterprise' => false,
            'description' => 'Reimbursement requests and approvals.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.reimbursements.view'],
                'manage' => ['label' => 'Manage', 'permission' => 'admin.reimbursements.manage'],
                'approve_l1' => ['label' => 'Approve Level 1', 'permission' => 'admin.reimbursements_l1.approve'],
                'approve_l2' => ['label' => 'Approve Level 2', 'permission' => 'admin.reimbursements_l2.approve'],
            ],
        ],
        'loans' => [
            'label' => 'Loans',
            'section' => 'finance',
            'enterprise' => true,
            'description' => 'Employee loan records.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.loans.view'],
                'manage' => ['label' => 'Manage', 'permission' => 'admin.loans.manage'],
            ],
        ],
        'cash_advances' => [
            'label' => 'Cash Advances',
            'section' => 'finance',
            'enterprise' => false,
            'description' => 'Cash advance (kasbon) management.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.cash_advances.manage'],
            ],
        ],
        'assets' => [
            'label' => 'Assets',
            'section' => 'finance',
            'enterprise' => false,
            'description' => 'Company asset assignments.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.assets.view'],
                'manage' => ['label' => 'Manage', 'permission' => 'admin.assets.manage'],
            ],
        ],
        'payroll' => [
            'label' => 'Payroll',
            'section' => 'finance',
            'enterprise' => false,
            'description' => 'Payroll runs, payslips and tax/BPJS configuration.',
            'actions' => [
                'view' => ['label' => 'View Payrolls', 'permission' => 'admin.payrolls.view'],
                'process' => ['label' => 'Process', 'permission' => 'admin.payroll.process'],
                'view_payslip' => ['label' => 'View Payslips', 'permission' => 'admin.payslip.view'],
                'download_payslip' => ['label' => 'Download Payslips', 'permission' => 'admin.payslip.download'],
                'manage_tax_configs' => ['label' => 'Manage Tax Configs', 'permission' => 'admin.tax_configs.manage'],
                'manage_bpjs_configs' => ['label' => 'Manage BPJS Configs', 'permission' => 'admin.bpjs_configs.manage'],
                'manage_settings' => ['label' => 'Manage Settings', 'permission' => 'admin.payroll_settings.manage'],
            ],
        ],

        // ── Operations ───────────────────────────────────────────────────
        'operations_workspace' => [
            'label' => 'Operations',
            'section' => 'operations',
            'enterprise' => true,
            'description' => 'Operations workspace.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.operations_workspace.view'],
                'manage' => ['label' => 'Manage', 'permission' => 'admin.operations_workspace.manage'],
            ],
        ],
        'collaboration_workspace' => [
            'label' => 'Collaboration',
            'section' => 'operations',
            'enterprise' => true,
            'description' => 'Collaboration workspace.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.collaboration_workspace.view'],
                'manage' => ['label' => 'Manage', 'permission' => 'admin.collaboration_workspace.manage'],
            ],
        ],
        'custom_forms' => [
            'label' => 'Custom Forms',
            'section' => 'operations',
            'enterprise' => true,
            'description' => 'Custom form definitions and submissions.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.custom_forms.view'],
                'manage' => ['label' => 'Manage', 'permission' => 'admin.custom_forms.manage'],
            ],
        ],

        // ── Knowledge ────────────────────────────────────────────────────
        'knowledge_base' => [
            'label' => 'Knowledge Base',
            'section' => 'knowledge',
            'enterprise' => false,
            'description' => 'AI knowledge base and RAG content.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.knowledgebase.view'],
                'manage' => ['label' => 'Manage', 'permission' => 'admin.knowledgebase.manage'],
            ],
        ],
        'appraisals' => [
            'label' => 'Appraisals',
            'section' => 'knowledge',
            'enterprise' => false,
            'description' => 'Employee performance appraisals.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.appraisals.view'],
            ],
        ],

        // ── Reports & Import ─────────────────────────────────────────────
        'reports' => [
            'label' => 'Reports',
            'section' => 'reports',
            'enterprise' => false,
            'description' => 'Operational reports and admin exports.',
            'actions' => [
                'view' => ['label' => 'View Reports', 'permission' => 'admin.operational_reports.view'],
                'export_admin' => ['label' => 'Export Admin Reports', 'permission' => 'admin.reports.export'],
                'export_logs' => ['label' => 'Export Activity Logs', 'permission' => 'admin.activity_logs.export'],
            ],
        ],
        'user_import_export' => [
            'label' => 'User Import/Export',
            'section' => 'reports',
            'enterprise' => false,
            'description' => 'User data import and export.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.user_import_export.view'],
                'export' => ['label' => 'Export Users', 'permission' => 'admin.users.export'],
                'import' => ['label' => 'Import Users', 'permission' => 'admin.users.import'],
            ],
        ],

        // ── Audit & Security ─────────────────────────────────────────────
        'activity_logs' => [
            'label' => 'Activity Logs',
            'section' => 'audit',
            'enterprise' => false,
            'description' => 'User activity trail.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.activity_logs.view'],
            ],
        ],
        'audit_logs' => [
            'label' => 'Audit Logs',
            'section' => 'audit',
            'enterprise' => false,
            'description' => 'Security audit trail.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.audit_logs.view'],
            ],
        ],
        'user_sessions' => [
            'label' => 'User Sessions',
            'section' => 'audit',
            'enterprise' => false,
            'description' => 'Active user session management.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.user_sessions.manage'],
            ],
        ],

        // ── Access Control ───────────────────────────────────────────────
        'rbac' => [
            'label' => 'Roles & Permissions',
            'section' => 'access',
            'enterprise' => false,
            'description' => 'Role and permission management.',
            'actions' => [
                'manage' => ['label' => 'Manage RBAC', 'permission' => 'admin.rbac.manage'],
                'assign' => ['label' => 'Assign Roles', 'permission' => 'admin.roles.assign'],
                // `accessAdminPanel` has no snake_case legacy mapping, so the
                // exact enum value is used (hasPermission matches it directly).
                'access' => ['label' => 'Access Admin Panel', 'permission' => 'accessAdminPanel'],
            ],
        ],

        // ── System & Settings ────────────────────────────────────────────
        'settings' => [
            'label' => 'Settings',
            'section' => 'system',
            'enterprise' => false,
            'description' => 'Admin settings pages.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.settings.view'],
            ],
        ],
        'system_settings' => [
            'label' => 'System Settings',
            'section' => 'system',
            'enterprise' => false,
            'description' => 'System-wide settings.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.system_settings.manage'],
            ],
        ],
        'system_maintenance' => [
            'label' => 'System Maintenance',
            'section' => 'system',
            'enterprise' => false,
            'description' => 'Maintenance mode and backup management.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.system_maintenance.manage'],
            ],
        ],
        'kpi_settings' => [
            'label' => 'KPI Settings',
            'section' => 'system',
            'enterprise' => false,
            'description' => 'KPI configuration.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.kpi_settings.manage'],
            ],
        ],
        'api_integrations' => [
            'label' => 'Integrations',
            'section' => 'system',
            'enterprise' => true,
            'description' => 'API integrations.',
            'actions' => [
                'manage' => ['label' => 'Manage', 'permission' => 'admin.api_integrations.manage'],
            ],
        ],
        'notifications' => [
            'label' => 'Notifications',
            'section' => 'system',
            'enterprise' => false,
            'description' => 'Notification preferences and admin notifications.',
            'actions' => [
                'view' => ['label' => 'View', 'permission' => 'admin.notifications.view'],
                'manage' => ['label' => 'Manage Admin Notifications', 'permission' => 'admin.notifications.manage'],
            ],
        ],
    ],

    'presets' => [
        'super-admin' => [
            'name' => 'Super Admin',
            'description' => 'Full system access.',
            'permissions' => ['*' => ['*']],
            'is_system' => true,
            'is_super_admin' => true,
        ],
    ],
];
