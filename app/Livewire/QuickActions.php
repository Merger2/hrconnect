<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class QuickActions extends Component
{
    public function render()
    {
        $user = Auth::user();
        $primaryItems = [
            [
                'href' => route('attendance.clock-in'),
                'label' => __('Absen'),
                'description' => __('Absen dengan wajah'),
                'icon' => 'fact_check',
                'tone' => 'bg-teal-100 text-teal-700',
            ],
            [
                'href' => route('leaves.apply'),
                'label' => __('Cuti'),
                'description' => __('Ajukan cuti'),
                'icon' => 'calendar_month',
                'tone' => 'bg-sky-100 text-sky-700',
            ],
            [
                'href' => route('overtimes.apply'),
                'label' => __('Lembur'),
                'description' => __('Ajukan lembur'),
                'icon' => 'schedule',
                'tone' => 'bg-amber-100 text-amber-700',
            ],
            [
                'href' => route('reimbursements.apply'),
                'label' => __('Klaim'),
                'description' => __('Ajukan klaim'),
                'icon' => 'receipt_long',
                'tone' => 'bg-rose-100 text-rose-700',
            ],
            [
                'href' => route('payroll.index'),
                'label' => __('Payroll'),
                'description' => __('Lihat slip gaji'),
                'icon' => 'payments',
                'tone' => 'bg-violet-100 text-violet-700',
            ],
        ];

        $moreGroups = [
            __('Attendance') => [
                ['href' => route('attendance.index'), 'label' => __('Riwayat Absen'), 'icon' => 'history', 'tone' => 'bg-sky-100 text-sky-700'],
            ],
        ];

        if ($user->can('approve_leaves_l1') || $user->can('approve_overtimes_l1') || $user->can('approve_reimbursements_l1')) {
            $moreGroups[__('Approvals')] = [
                ['href' => route('approvals.index'), 'label' => __('Persetujuan'), 'icon' => 'approval', 'tone' => 'bg-emerald-100 text-emerald-700'],
            ];
        }

        $moreGroups[__('Knowledge')] = [
            ['href' => route('knowledge-base.index'), 'label' => __('AI Chat'), 'icon' => 'smart_toy', 'tone' => 'bg-purple-100 text-purple-700'],
        ];

        if ($user->can('manage_knowledgebase')) {
            $moreGroups[__('Knowledge')][] = [
                'href' => route('knowledge-base.manage'), 'label' => __('Kelola Dokumen'), 'icon' => 'description', 'tone' => 'bg-fuchsia-100 text-fuchsia-700',
            ];
        }

        $moreGroups[__('Settings')] = [
            ['href' => route('profile.edit'), 'label' => __('Profil'), 'icon' => 'person', 'tone' => 'bg-blue-100 text-blue-700'],
            ['href' => route('security.edit'), 'label' => __('Keamanan'), 'icon' => 'security', 'tone' => 'bg-gray-100 text-gray-700'],
        ];

        return view('livewire.quick-actions', [
            'primaryItems' => $primaryItems,
            'moreGroups' => $moreGroups,
            'flattenedMoreItems' => collect($moreGroups)->flatten(1)->all(),
        ]);
    }
}
