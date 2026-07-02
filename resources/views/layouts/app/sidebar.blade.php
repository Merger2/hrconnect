<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas pb-20 lg:pb-0"
        x-data
        x-init="$store.darkMode.init()"
    >
        <!-- ─── Desktop Sidebar ─── -->
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-e border-outline-variant bg-canvas lg:flex">
            {{-- Logo --}}
            <div class="flex h-16 items-center px-4">
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-2">
                @can('view_dashboard')
                <div class="px-4 pb-2 pt-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Utama') }}</p>
                </div>

                <a href="{{ route('dashboard') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('dashboard'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('dashboard')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">home</span>
                    <span>{{ __('Dashboard') }}</span>
                </a>
                @endcan

                @php
                    $hasSdmAccess = auth()->user()->can('viewAny', App\Models\Employee::class)
                        || auth()->user()->can('view_attendances')
                        || auth()->user()->can('view_leaves')
                        || auth()->user()->can('view_overtimes')
                        || auth()->user()->can('view_reimbursements')
                        || auth()->user()->can('view_loans')
                        || auth()->user()->can('view_assets')
                        || auth()->user()->can('view_payrolls');
                @endphp

                @if($hasSdmAccess)
                <div class="px-4 pb-2 pt-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('SDM') }}</p>
                </div>
                @endif

                @can('viewAny', App\Models\Employee::class)
                    @php $empLabel = auth()->user()->hasRole('manager') ? __('Anggota Tim') : __('Direktori Karyawan') @endphp
                    <a href="{{ route('admin.employees.index') }}"
                       @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                               'bg-ink/5 text-ink' => request()->routeIs('admin.employees.*'),
                               'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('admin.employees.*')])
                       wire:navigate>
                        <span class="material-symbols-outlined text-2xl">group</span>
                        <span>{{ $empLabel }}</span>
                    </a>
                @endcan

                @can('manage_attendances')
                {{-- TODO: Implement admin attendance management page --}}
                @endcan

                @can('view_attendances')
                <a href="{{ route('attendance.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('attendance.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('attendance.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">schedule</span>
                    <span>{{ __('Absensi') }}</span>
                </a>
                @endcan

                @can('view_leaves')
                <a href="{{ route('leaves.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('leaves.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('leaves.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">calendar_month</span>
                    <span>{{ __('Cuti') }}</span>
                </a>
                @endcan

                @can('view_overtimes')
                <a href="{{ route('overtimes.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('overtimes.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('overtimes.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">bolt</span>
                    <span>{{ __('Lembur') }}</span>
                </a>
                @endcan

                @can('view_reimbursements')
                <a href="{{ route('reimbursements.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('reimbursements.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('reimbursements.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">wallet</span>
                    <span>{{ __('Klaim') }}</span>
                </a>
                @endcan

                @can('view_loans')
                <a href="{{ route('loans.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('loans.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('loans.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">account_balance</span>
                    <span>{{ __('Pinjaman') }}</span>
                </a>
                @endcan

                @can('view_assets')
                <a href="{{ route('assets.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('assets.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('assets.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">inventory_2</span>
                    <span>{{ __('Aset') }}</span>
                </a>
                @endcan

                @can('view_payrolls')
                <a href="{{ route('payroll.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('payroll.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('payroll.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">payments</span>
                    <span>{{ __('Penggajian') }}</span>
                </a>
                @endcan

                {{-- Persetujuan --}}
                @php
                    $canApprove = auth()->user()->hasAnyPermission([
                        'approve_leaves_l1', 'approve_leaves_l2',
                        'approve_overtimes_l1', 'approve_overtimes_l2',
                        'approve_reimbursements_l1', 'approve_reimbursements_l2',
                        'approve_wfa',
                    ]);
                @endphp
                @if($canApprove)
                <div class="px-4 pb-2 pt-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Persetujuan') }}</p>
                </div>

                <a href="{{ route('approvals.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('approvals.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('approvals.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">approval</span>
                    <span>{{ __('Semua Persetujuan') }}</span>
                </a>
                @endif

                {{-- Other modules --}}
                @can('view_knowledgebase')
                <div class="px-4 pb-2 pt-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Lainnya') }}</p>
                </div>

                <a href="{{ route('knowledge-base.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('knowledge-base.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('knowledge-base.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">menu_book</span>
                    <span>{{ __('Basis Pengetahuan') }}</span>
                </a>
                @endcan

                {{-- Master Data (HR only) — TODO: implement routes for company structure --}}
                @canany(['manage_companies', 'manage_branches', 'manage_departments', 'manage_positions', 'manage_holidays', 'manage_shifts'])
                @endcanany
            </nav>

            {{-- User --}}
            <div class="flex items-center justify-between border-t border-outline-variant p-3">
                <x-desktop-user-menu />
                <x-navigation.theme-toggle size="sm" />
            </div>
        </aside>

        <!-- ─── Mobile Header ─── -->
        <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-outline-variant bg-canvas px-4 lg:hidden">
            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
            <div class="flex items-center gap-1">
                <x-navigation.theme-toggle size="sm" />
                <x-desktop-user-menu />
            </div>
        </header>

        <!-- ─── Main Content ─── -->
        <div class="px-4 py-4 lg:ms-64 lg:px-6 lg:py-6">
            {{ $slot }}
        </div>

        {{-- Mobile Bottom Navigation --}}
        <x-bottom-nav />

        @persist('toast')
            <div id="toast-container"></div>
        @endpersist

        @auth
            @php
                if (!session()->has('web_sanctum_token')) {
                    $token = auth()->user()->createToken('web-frontend');
                    session()->put('web_sanctum_token', $token->plainTextToken);
                }
            @endphp
            <script>
                window.Laravel = { sanctumToken: '{{ session('web_sanctum_token') }}' };
            </script>
        @endauth

        @vite(['resources/js/app.js'])
        @stack('scripts')
    </body>
</html>
