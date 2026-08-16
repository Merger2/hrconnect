@php
    $currentUser = request()->user();
    $profileNotificationCount = 0;

    if ($currentUser) {
        $profileNotificationCount = $currentUser->unreadNotifications()->count()
            + \App\Models\Announcement::visibleForUser($currentUser->id)->count();
    }

    $items = [
        [
            'label' => __('Home'),
            'href' => route('home'),
            'active' => request()->routeIs('home'),
            'icon' => 'heroicon-o-home',
        ],
        [
            'label' => __('Schedule'),
            'href' => route('my-schedule'),
            'active' => request()->routeIs('my-schedule', 'shift-swap-requests', 'wfh-requests'),
            'icon' => 'heroicon-o-calendar-days',
        ],
        [
            'label' => __('Absen'),
            'href' => route('scan'),
            'active' => request()->routeIs('scan', 'face.enrollment', 'attendance-history', 'attendance-corrections'),
            'icon' => 'heroicon-o-camera',
        ],
        [
            'label' => __('Tasks'),
            'href' => route('hr-tasks'),
            'active' => request()->routeIs('hr-tasks', 'my-tasks', 'my-forms', 'approvals', 'approvals.history'),
            'icon' => 'heroicon-o-clipboard-document-check',
        ],
        [
            'label' => __('Profile'),
            'href' => route('profile.show'),
            'active' => request()->routeIs('profile.show'),
            'icon' => 'heroicon-o-user-circle',
            'badge' => $profileNotificationCount,
        ],
    ];
@endphp

<nav
    aria-label="{{ __('User navigation') }}"
    class="user-bottom-navigation md:hidden"
>
    <div class="user-bottom-navigation__dock">
        @foreach ($items as $item)
            @php
                $active = (bool) $item['active'];
                $badgeCount = (int) ($item['badge'] ?? 0);
            @endphp

            <a
                href="{{ $item['href'] }}"
                @if ($active) aria-current="page" @endif
                aria-label="{{ $badgeCount > 0 ? __(':label, :count unread', ['label' => $item['label'], 'count' => $badgeCount]) : $item['label'] }}"
                class="group user-bottom-navigation__item {{ $active ? 'is-active' : '' }}"
            >
                <span class="user-bottom-navigation__icon">
                    <x-dynamic-component :component="$item['icon']" class="h-5 w-5" />

                    @if ($badgeCount > 0)
                        <span class="user-bottom-navigation__badge" aria-label="{{ __('Unread notifications') }}: {{ $badgeCount }}">
                            {{ $badgeCount > 99 ? '99+' : $badgeCount }}
                        </span>
                    @endif
                </span>
                <span class="user-bottom-navigation__label">
                    {{ $item['label'] }}
                </span>
            </a>
        @endforeach
    </div>
</nav>
