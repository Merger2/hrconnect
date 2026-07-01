@php
    use Illuminate\Support\Facades\Auth;

    $user = Auth::user();

    $allTabs = [
        [
            'name' => __('Dasbor'),
            'icon' => 'home',
            'route' => 'dashboard',
            'patterns' => ['dashboard'],
            'can' => 'view_dashboard',
        ],
        [
            'name' => __('Absen'),
            'icon' => 'fact_check',
            'route' => 'attendance.index',
            'patterns' => ['attendance.*'],
            'can' => 'view_attendances',
        ],
        [
            'name' => __('Pengajuan'),
            'icon' => 'description',
            'route' => 'leaves.index',
            'patterns' => ['leaves.*', 'overtimes.*', 'reimbursements.*', 'approvals.*'],
            'can' => ['view_leaves', 'view_overtimes', 'view_reimbursements', 'approve_leaves_l1', 'approve_leaves_l2', 'approve_overtimes_l1', 'approve_overtimes_l2', 'approve_reimbursements_l1', 'approve_reimbursements_l2', 'approve_wfa'],
        ],
        [
            'name' => __('Payroll'),
            'icon' => 'payments',
            'route' => 'payroll.index',
            'patterns' => ['payroll.*'],
            'can' => 'view_payrolls',
        ],
        [
            'name' => __('Profil'),
            'icon' => 'person',
            'route' => 'profile.edit',
            'patterns' => ['profile.edit', 'security.edit', 'appearance.edit'],
            'can' => null,
        ],
    ];

    $tabs = array_values(array_filter($allTabs, function ($tab) use ($user) {
        if ($tab['can'] === null) return true;
        $perms = (array) $tab['can'];
        foreach ($perms as $perm) {
            if ($user->can($perm)) return true;
        }
        return false;
    }));
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 flex h-20 items-center justify-around border-t border-outline-variant bg-canvas pb-[env(safe-area-inset-bottom)] lg:hidden">
    @foreach ($tabs as $tab)
        @php
            $isActive = !empty($tab['patterns']) && request()->routeIs(...$tab['patterns']);
        @endphp

        <a href="{{ route($tab['route']) }}"
           @class([
               'flex h-full flex-1 flex-col items-center justify-center gap-0.5 transition-colors relative',
               'text-ink' => $isActive,
               'text-on-surface-variant hover:text-ink' => !$isActive,
           ])
           wire:navigate>
            <span class="material-symbols-outlined text-2xl">
                {{ $tab['icon'] }}
            </span>
            <span class="text-xs font-medium leading-tight">{{ $tab['name'] }}</span>
            @if ($isActive)
                <span class="absolute bottom-0 h-1 w-6 rounded-full bg-ink"></span>
            @endif
        </a>
    @endforeach
</nav>
