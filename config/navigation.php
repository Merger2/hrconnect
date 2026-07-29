<?php

return [
    'groups' => [
        [
            'title' => 'Utama',
            'roles' => ['super-admin', 'hr', 'manager', 'employee'],
            'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'can' => 'view_dashboard'],
            ],
        ],
        [
            'title' => 'SDM',
            'roles' => ['super-admin', 'hr', 'manager', 'employee'],
            'items' => [
                [
                    'label' => 'Direktori Karyawan',
                    'route' => 'admin.employees.index',
                    'icon' => 'group',
                    'can' => 'viewAny,App\\Models\\Employee',
                    'label_for' => ['manager' => 'Anggota Tim'],
                ],
                ['label' => 'Absensi', 'route' => 'attendance.index', 'icon' => 'schedule', 'can' => 'view_attendances'],
                ['label' => 'Registrasi Wajah', 'route' => 'attendance.face-registration', 'icon' => 'face', 'can' => 'view_attendances'],
                ['label' => 'Cuti', 'route' => 'leaves.index', 'icon' => 'calendar_month', 'can' => 'view_leaves'],
                ['label' => 'Lembur', 'route' => 'overtimes.index', 'icon' => 'bolt', 'can' => 'view_overtimes'],
                ['label' => 'Klaim', 'route' => 'reimbursements.index', 'icon' => 'wallet', 'can' => 'view_reimbursements'],
            ],
        ],
        [
            'title' => 'Keuangan',
            'roles' => ['super-admin', 'hr', 'employee'],
            'items' => [
                ['label' => 'Pinjaman', 'route' => 'loans.index', 'icon' => 'account_balance', 'can' => 'view_loans'],
                ['label' => 'Aset', 'route' => 'assets.index', 'icon' => 'inventory_2', 'can' => 'view_assets'],
                ['label' => 'Slip Gaji', 'route' => 'payroll.index', 'icon' => 'payments', 'can' => 'view_payslip'],
            ],
        ],
        [
            'title' => 'Admin',
            'roles' => ['super-admin', 'hr'],
            'items' => [
                ['label' => 'Manajemen Penggajian', 'route' => 'admin.payroll.index', 'icon' => 'summarize', 'can' => 'view_payrolls'],
                ['label' => 'Matriks Absensi', 'route' => 'admin.attendance.index', 'icon' => 'grid_view', 'can' => 'view_attendances'],
                ['label' => 'Kelola Cuti', 'route' => 'admin.leaves.index', 'icon' => 'event_available', 'can' => 'view_leaves'],
                ['label' => 'Kelola Lembur', 'route' => 'admin.overtimes.index', 'icon' => 'bolt', 'can' => 'view_overtimes'],
                ['label' => 'Kelola Reimbursement', 'route' => 'admin.reimbursements.index', 'icon' => 'receipt_long', 'can' => 'view_reimbursements'],
            ],
        ],
        [
            'title' => 'IT Support',
            'roles' => ['super-admin', 'it-support'],
            'items' => [
                ['label' => 'Monitoring Dashboard', 'route' => 'monitoring', 'icon' => 'monitoring', 'can' => 'view_activity_logs'],
            ],
        ],
        [
            'title' => 'Persetujuan',
            'roles' => ['super-admin', 'hr', 'manager'],
            'items' => [
                ['label' => 'Semua Persetujuan', 'route' => 'approvals.index', 'icon' => 'approval', 'can' => 'viewAny,App\\Models\\Approval'],
            ],
        ],
        [
            'title' => 'Lainnya',
            'roles' => ['super-admin', 'hr', 'employee'],
            'items' => [
                ['label' => 'Basis Pengetahuan', 'route' => 'knowledge-base.manage', 'icon' => 'menu_book', 'can' => 'manage_knowledgebase'],
            ],
        ],
        [
            'title' => 'Master Data',
            'roles' => ['super-admin', 'hr'],
            'items' => [
                ['label' => 'Cabang', 'route' => 'master-data.branches', 'icon' => 'location_on', 'can' => 'view_branches'],
                ['label' => 'Departemen', 'route' => 'master-data.departments', 'icon' => 'account_tree', 'can' => 'view_departments'],
                ['label' => 'Jabatan', 'route' => 'master-data.positions', 'icon' => 'work', 'can' => 'view_positions'],
                ['label' => 'Shift Kerja', 'route' => 'master-data.shifts', 'icon' => 'schedule', 'can' => 'view_branches'],
                ['label' => 'Hari Libur', 'route' => 'master-data.holidays', 'icon' => 'event_busy', 'can' => 'view_branches'],
                ['label' => 'Tipe Cuti', 'route' => 'master-data.leave-types', 'icon' => 'calendar_month', 'can' => 'view_branches'],
            ],
        ],
    ],
];
