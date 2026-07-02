@php
    use Illuminate\Support\Facades\Auth;

    $user = Auth::user();
    $profileBadge = $user ? $user->unreadNotifications()->count() : 0;

    $allTabs = [
        [
            'label' => __('Dasbor'),
            'icon' => 'home',
            'route' => 'dashboard',
            'active' => request()->routeIs('dashboard'),
            'can' => 'view_dashboard',
        ],
        [
            'label' => __('Absen'),
            'icon' => 'fact_check',
            'route' => 'attendance.index',
            'active' => request()->routeIs('attendance.*'),
            'can' => 'view_attendances',
        ],
        [
            'label' => __('Pengajuan'),
            'icon' => 'description',
            'route' => 'leaves.index',
            'active' => request()->routeIs(['leaves.*', 'overtimes.*', 'reimbursements.*', 'approvals.*']),
            'can' => ['view_leaves', 'view_overtimes', 'view_reimbursements'],
        ],
        [
            'label' => __('Payroll'),
            'icon' => 'payments',
            'route' => 'payroll.index',
            'active' => request()->routeIs('payroll.*'),
            'can' => 'view_payrolls',
        ],
        [
            'label' => __('Profil'),
            'icon' => 'person',
            'route' => 'profile.edit',
            'active' => request()->routeIs(['profile.edit', 'security.edit', 'appearance.edit']),
            'can' => null,
            'badge' => $profileBadge,
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

<nav aria-label="{{ __('Navigasi pengguna') }}" class="fixed inset-x-0 bottom-0 z-40 px-3 pb-[max(0.5rem,calc(env(safe-area-inset-bottom)+0.5rem))] sm:px-6 lg:hidden">
    <div class="mx-auto grid h-[4.15rem] max-w-md grid-cols-5 items-center gap-0.5 overflow-visible rounded-[1.45rem] border border-outline-variant/70 bg-canvas/88 px-1.5 py-1.5 shadow-[0_18px_44px_-34px_rgba(10,10,10,0.6)] backdrop-blur-xl">
        @foreach ($tabs as $tab)
            @php
                $isActive = (bool) $tab['active'];
                $badgeCount = (int) ($tab['badge'] ?? 0);
            @endphp
            <a href="{{ route($tab['route']) }}"
               @if ($isActive) aria-current="page" @endif
               aria-label="{{ $badgeCount > 0 ? __(':label, :count belum dibaca', ['label' => $tab['label'], 'count' => $badgeCount]) : $tab['label'] }}"
               @class([
                   'group relative flex min-h-[3.2rem] min-w-0 flex-col items-center justify-center gap-0.5 rounded-[1.05rem] px-1 text-center transition duration-200',
                   'bg-ink/5 text-ink' => $isActive,
                   'text-on-surface-variant hover:bg-surface-dim/50 hover:text-ink' => !$isActive,
               ])
               wire:navigate>
                <span @class([
                    'relative grid h-7 w-7 place-items-center rounded-full text-current transition duration-200',
                    'bg-ink/10 text-ink' => $isActive,
                ])>
                    <span class="material-symbols-outlined text-xl transition duration-200">
                        {{ $tab['icon'] }}
                    </span>

                    @if ($badgeCount > 0)
                        <span class="absolute -right-1 -top-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-error px-1 text-[0.56rem] font-bold leading-none text-white ring-2 ring-canvas">
                            {{ $badgeCount > 99 ? '99+' : $badgeCount }}
                        </span>
                    @endif
                </span>
                <span class="relative block w-full truncate text-[0.68rem] font-semibold leading-tight">
                    {{ $tab['label'] }}
                </span>
            </a>
        @endforeach
    </div>
</nav>
