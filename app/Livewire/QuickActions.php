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
                'tone' => 'tone-primary',
            ],
            [
                'href' => route('leaves.apply'),
                'label' => __('Cuti'),
                'description' => __('Ajukan cuti'),
                'icon' => 'calendar_month',
                'tone' => 'tone-info',
            ],
            [
                'href' => route('overtimes.apply'),
                'label' => __('Lembur'),
                'description' => __('Ajukan lembur'),
                'icon' => 'schedule',
                'tone' => 'tone-warning',
            ],
            [
                'href' => route('reimbursements.apply'),
                'label' => __('Klaim'),
                'description' => __('Ajukan klaim'),
                'icon' => 'receipt_long',
                'tone' => 'tone-error',
            ],
            [
                'href' => route('payroll.index'),
                'label' => __('Payroll'),
                'description' => __('Lihat slip gaji'),
                'icon' => 'payments',
                'tone' => 'tone-brand',
            ],
        ];

        $moreGroups = [
            __('Attendance') => [
                ['href' => route('attendance.index'), 'label' => __('Riwayat Absen'), 'icon' => 'history', 'tone' => 'tone-info'],
            ],
        ];

        if ($user->can('approve_leaves_l1') || $user->can('approve_overtimes_l1') || $user->can('approve_reimbursements_l1')) {
            $moreGroups[__('Approvals')] = [
                ['href' => route('approvals.index'), 'label' => __('Persetujuan'), 'icon' => 'approval', 'tone' => 'tone-success'],
            ];
        }

        $moreGroups[__('Knowledge')] = [
            ['href' => route('knowledge-base.index'), 'label' => __('AI Chat'), 'icon' => 'smart_toy', 'tone' => 'tone-brand'],
        ];

        if ($user->can('manage_knowledgebase')) {
            $moreGroups[__('Knowledge')][] = [
                'href' => route('knowledge-base.manage'), 'label' => __('Kelola Dokumen'), 'icon' => 'description', 'tone' => 'tone-secondary',
            ];
        }

        $moreGroups[__('Settings')] = [
            ['href' => route('profile.edit'), 'label' => __('Profil'), 'icon' => 'person', 'tone' => 'tone-primary'],
            ['href' => route('security.edit'), 'label' => __('Keamanan'), 'icon' => 'security', 'tone' => 'tone-neutral'],
        ];

        return view('livewire.quick-actions', [
            'primaryItems' => $primaryItems,
            'moreGroups' => $moreGroups,
            'flattenedMoreItems' => collect($moreGroups)->flatten(1)->all(),
        ]);
    }
}
