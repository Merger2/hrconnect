<?php

return [
    'groups' => [
        [
            'title' => 'Utama',
            'roles' => ['super-admin', 'hr', 'manager', 'employee'],
            'items' => [
                ['label' => 'Dashboard', 'route' => 'home', 'icon' => 'home', 'can' => 'view_dashboard'],
            ],
        ],
        [
            'title' => 'SDM',
            'roles' => ['super-admin', 'hr', 'manager', 'employee'],
            'items' => [
                [
                    'label' => 'Direktori Karyawan',
                    'route' => 'admin.employees',
                    'icon' => 'group',
                    'can' => 'viewAny,App\\Models\\Employee',
                    'label_for' => ['manager' => 'Anggota Tim'],
                ],
                ['label' => 'Absensi', 'route' => 'attendance-history', 'icon' => 'schedule', 'can' => 'view_attendances'],
                ['label' => 'Registrasi Wajah', 'route' => 'face.enrollment', 'icon' => 'face', 'can' => 'view_attendances'],
                ['label' => 'Cuti', 'route' => 'apply-leave', 'icon' => 'calendar_month', 'can' => 'view_leaves'],
                ['label' => 'Lembur', 'route' => 'overtime', 'icon' => 'bolt', 'can' => 'view_overtimes'],
                ['label' => 'Klaim', 'route' => 'reimbursement', 'icon' => 'wallet', 'can' => 'view_reimbursements'],
            ],
        ],
        [
            'title' => 'Keuangan',
            'roles' => ['super-admin', 'hr', 'employee'],
            'items' => [
                ['label' => 'Aset', 'route' => 'my-assets', 'icon' => 'inventory_2', 'can' => 'view_assets'],
                ['label' => 'Slip Gaji', 'route' => 'my-payslips', 'icon' => 'payments', 'can' => 'view_payslip'],
            ],
        ],
        [
            'title' => 'Admin',
            'roles' => ['super-admin', 'hr'],
            'items' => [
                ['label' => 'Manajemen Penggajian', 'route' => 'admin.payrolls', 'icon' => 'summarize', 'can' => 'view_payrolls'],
                ['label' => 'Matriks Absensi', 'route' => 'admin.attendances', 'icon' => 'grid_view', 'can' => 'view_attendances'],
                ['label' => 'Kelola Cuti', 'route' => 'admin.leaves', 'icon' => 'event_available', 'can' => 'view_leaves'],
                ['label' => 'Kelola Lembur', 'route' => 'admin.overtime', 'icon' => 'bolt', 'can' => 'view_overtimes'],
                ['label' => 'Kelola Reimbursement', 'route' => 'admin.reimbursements', 'icon' => 'receipt_long', 'can' => 'view_reimbursements'],
            ],
        ],
        [
            'title' => 'Persetujuan',
            'roles' => ['super-admin', 'hr', 'manager'],
            'items' => [
                ['label' => 'Semua Persetujuan', 'route' => 'approvals', 'icon' => 'approval', 'can' => 'viewAny,App\\Models\\Approval'],
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
                ['label' => 'Cabang', 'route' => 'admin.companies', 'icon' => 'location_on', 'can' => 'view_branches'],
                ['label' => 'Departemen', 'route' => 'admin.masters.division', 'icon' => 'account_tree', 'can' => 'view_departments'],
                ['label' => 'Jabatan', 'route' => 'admin.masters.job-title', 'icon' => 'work', 'can' => 'view_positions'],
                ['label' => 'Shift Kerja', 'route' => 'admin.masters.shift', 'icon' => 'schedule', 'can' => 'view_branches'],
                ['label' => 'Hari Libur', 'route' => 'admin.holidays', 'icon' => 'event_busy', 'can' => 'view_branches'],
                ['label' => 'Tipe Cuti', 'route' => 'admin.masters.leave-types', 'icon' => 'calendar_month', 'can' => 'view_branches'],
            ],
        ],
    ],
];
