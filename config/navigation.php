<?php

return [
    'groups' => [
        [
            'title' => 'Utama',
            'roles' => ['super-admin', 'hr-manager', 'finance', 'manager', 'employee'],
            'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'can' => 'view_dashboard'],
            ],
        ],
        [
            'title' => 'SDM',
            'roles' => ['super-admin', 'hr-manager', 'manager'],
            'items' => [
                [
                    'label' => 'Direktori Karyawan',
                    'route' => 'admin.employees.index',
                    'icon' => 'group',
                    'can' => 'viewAny,App\\Models\\Employee',
                    'label_for' => ['manager' => 'Anggota Tim'],
                ],
                ['label' => 'Absensi', 'route' => 'attendance.index', 'icon' => 'schedule', 'can' => 'view_attendances'],
                ['label' => 'Cuti', 'route' => 'leaves.index', 'icon' => 'calendar_month', 'can' => 'view_leaves'],
                ['label' => 'Lembur', 'route' => 'overtimes.index', 'icon' => 'bolt', 'can' => 'view_overtimes'],
                ['label' => 'Klaim', 'route' => 'reimbursements.index', 'icon' => 'wallet', 'can' => 'view_reimbursements'],
            ],
        ],
        [
            'title' => 'Keuangan',
            'roles' => ['super-admin', 'hr-manager', 'finance'],
            'items' => [
                ['label' => 'Pinjaman', 'route' => 'loans.index', 'icon' => 'account_balance', 'can' => 'view_loans'],
                ['label' => 'Aset', 'route' => 'assets.index', 'icon' => 'inventory_2', 'can' => 'view_assets'],
                ['label' => 'Penggajian', 'route' => 'payroll.index', 'icon' => 'payments', 'can' => 'view_payrolls'],
            ],
        ],
        [
            'title' => 'Persetujuan',
            'roles' => ['super-admin', 'hr-manager', 'manager', 'finance'],
            'items' => [
                ['label' => 'Semua Persetujuan', 'route' => 'approvals.index', 'icon' => 'approval', 'can' => 'viewAny,App\\Models\\Approval'],
            ],
        ],
        [
            'title' => 'Lainnya',
            'roles' => ['super-admin', 'hr-manager'],
            'items' => [
                ['label' => 'Basis Pengetahuan', 'route' => 'knowledge-base.index', 'icon' => 'menu_book', 'can' => 'view_knowledgebase'],
            ],
        ],
    ],
];
