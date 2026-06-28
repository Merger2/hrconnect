<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;

class QuickActions extends Component
{
    public function render()
    {
        $primaryItems = [
            [
                'href' => route('attendance.clock-in'),
                'label' => __('Absen'),
                'description' => __('Clock in / out with face recognition'),
                'icon' => 'fact_check',
                'tone' => 'bg-teal-100 text-teal-700',
            ],
            [
                'href' => route('leaves.apply'),
                'label' => __('Cuti'),
                'description' => __('Apply for leave'),
                'icon' => 'calendar_month',
                'tone' => 'bg-sky-100 text-sky-700',
            ],
            [
                'href' => route('overtimes.apply'),
                'label' => __('Lembur'),
                'description' => __('Submit overtime request'),
                'icon' => 'schedule',
                'tone' => 'bg-amber-100 text-amber-700',
            ],
            [
                'href' => route('reimbursements.apply'),
                'label' => __('Klaim'),
                'description' => __('Submit reimbursement'),
                'icon' => 'receipt_long',
                'tone' => 'bg-rose-100 text-rose-700',
            ],
            [
                'href' => route('payroll.index'),
                'label' => __('Payroll'),
                'description' => __('View payslips'),
                'icon' => 'payments',
                'tone' => 'bg-violet-100 text-violet-700',
            ],
        ];

        $moreGroups = [
            __('Attendance') => [
                ['href' => route('attendance.index'), 'label' => __('Riwayat Absen'), 'icon' => 'history', 'tone' => 'bg-sky-100 text-sky-700'],
            ],
            __('Approvals') => [
                ['href' => route('approvals.index'), 'label' => __('Persetujuan'), 'icon' => 'approval', 'tone' => 'bg-emerald-100 text-emerald-700'],
            ],
            __('Knowledge') => [
                ['href' => route('knowledge-base.index'), 'label' => __('AI Chat'), 'icon' => 'smart_toy', 'tone' => 'bg-purple-100 text-purple-700'],
                ['href' => route('knowledge-base.manage'), 'label' => __('Kelola Dokumen'), 'icon' => 'description', 'tone' => 'bg-fuchsia-100 text-fuchsia-700'],
            ],
            __('Settings') => [
                ['href' => route('profile.edit'), 'label' => __('Profil'), 'icon' => 'person', 'tone' => 'bg-blue-100 text-blue-700'],
                ['href' => route('settings.security'), 'label' => __('Keamanan'), 'icon' => 'security', 'tone' => 'bg-gray-100 text-gray-700'],
            ],
        ];

        $flattenedMoreItems = collect($moreGroups)->flatten(1)->all();

        return view('livewire.quick-actions', [
            'primaryItems' => $primaryItems,
            'moreGroups' => $moreGroups,
            'flattenedMoreItems' => $flattenedMoreItems,
        ]);
    }
}
