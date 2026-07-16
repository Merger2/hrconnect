@php
    use Illuminate\Support\Facades\Auth;
    use App\Services\NavigationService;

    $user = Auth::user();
    $navigationService = new NavigationService();
    $menuGroups = $user ? $navigationService->build($user) : [];

    // Flatten for bottom nav (max 5 items, prioritize Utama + SDM)
    $priorityRoutes = ['dashboard', 'attendance.index', 'leaves.index', 'payroll.index', 'profile.edit'];
    $allItems = collect($menuGroups)->flatMap(fn ($g) => $g['items'])->keyBy('route');

    $bottomNavItems = collect($priorityRoutes)
        ->map(fn ($route) => $allItems[$route] ?? null)
        ->filter()
        ->take(5)
        ->values()
        ->map(function ($item) {
            $route = $item['route'];
            $activePatterns = $item['active_pattern'] ?? $route;
            $isActive = is_array($activePatterns)
                ? request()->routeIs(array_map(fn($p) => $p . '*', $activePatterns))
                : request()->routeIs($activePatterns . '*');

            return [
                'label' => __($item['label']),
                'icon' => $item['icon'],
                'route' => $route,
                'active' => $isActive,
            ];
        })
        ->all();

    // FAB route (always Clock In for employee/manager/hr)
    $showFab = $user && in_array($user->getRoleNames()->first(), ['employee', 'manager', 'hr', 'super-admin'], true);
    $fabRoute = route('attendance.clock-in');
@endphp

<nav aria-label="{{ __('Navigasi pengguna') }}" class="fixed inset-x-0 bottom-0 z-40 px-3 pb-[max(0.5rem,calc(env(safe-area-inset-bottom)+0.5rem))] sm:px-6 lg:hidden">
    {{-- FAB: Clock In --}}
    @if ($showFab)
        <div class="mb-4 flex justify-center pointer-events-none">
            <a href="{{ $fabRoute }}"
               wire:navigate
               class="pointer-events-auto flex h-14 w-14 items-center justify-center gap-2 rounded-full bg-primary text-on-primary shadow-[0_6px_16px_-4px_rgba(79,70,229,0.4)] transition-all duration-200 ease-[cubic-bezier(0.2,0,0,1)] hover:shadow-[0_8px_24px_-4px_rgba(79,70,229,0.5)] hover:scale-105 active:scale-[0.95] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
               aria-label="{{ __('Absen Sekarang') }}">
                <span class="material-symbols-outlined text-2xl">badge</span>
            </a>
        </div>
    @endif

    {{-- Bottom Nav Bar --}}
    <div class="mx-auto grid h-[4.15rem] max-w-md grid-cols-5 items-center gap-0.5 overflow-visible rounded-[1.45rem] border border-outline-variant/70 bg-canvas/88 px-1.5 py-1.5 shadow-[0_18px_44px_-34px_rgba(10,10,10,0.6)] backdrop-blur-xl">
        @foreach ($bottomNavItems as $tab)
            @php
                $isActive = (bool) $tab['active'];
            @endphp
            <a href="{{ route($tab['route']) }}"
               @if ($isActive) aria-current="page" @endif
               aria-label="{{ $tab['label'] }}"
               @class([
                   'group relative flex min-h-[3.2rem] min-w-0 flex-col items-center justify-center gap-0.5 rounded-[1.05rem] px-1 text-center transition duration-[var(--motion-duration-fast)] ease-[var(--motion-easing-standard)]',
                   'bg-primary/10 text-primary' => $isActive,
                   'text-on-surface-variant hover:bg-surface-container-low hover:text-ink' => !$isActive,
               ])
               wire:navigate>
                <span @class([
                    'relative grid h-7 w-7 place-items-center rounded-full text-current transition duration-[var(--motion-duration-fast)] ease-[var(--motion-easing-standard)]',
                    'bg-primary/10 text-primary' => $isActive,
                ])>
                    <span class="material-symbols-outlined text-xl transition duration-[var(--motion-duration-fast)]">
                        {{ $tab['icon'] }}
                    </span>
                </span>
                <span class="relative block w-full truncate text-[0.68rem] font-semibold leading-tight">
                    {{ $tab['label'] }}
                </span>

                {{-- Active indicator slide --}}
                @if ($isActive)
                    <span class="absolute bottom-0 left-1/2 -translate-x-1/2 h-0.5 w-8 rounded-full bg-primary transition-transform duration-[var(--motion-duration-normal)] ease-[var(--motion-easing-emphasized)]" aria-hidden="true"></span>
                @endif
            </a>
        @endforeach
    </div>
</nav>