<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas pb-20 lg:pb-0">
        <!-- ─── Desktop Sidebar ─── -->
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-e border-outline-variant bg-canvas lg:flex">
            {{-- Logo --}}
            <div class="flex h-16 items-center px-4">
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-2">
                <div class="px-4 pb-2 pt-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('Main') }}</p>
                </div>

                <a href="{{ route('dashboard') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('dashboard'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('dashboard')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">home</span>
                    <span>{{ __('Dashboard') }}</span>
                </a>

                <div class="px-4 pb-2 pt-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-on-surface-variant">{{ __('HR') }}</p>
                </div>

                <a href="{{ route('attendance.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('attendance.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('attendance.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">schedule</span>
                    <span>{{ __('Attendance') }}</span>
                </a>

                <a href="{{ route('leaves.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('leaves.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('leaves.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">calendar_month</span>
                    <span>{{ __('Leave') }}</span>
                </a>

                <a href="{{ route('overtimes.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('overtimes.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('overtimes.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">bolt</span>
                    <span>{{ __('Overtime') }}</span>
                </a>

                <a href="{{ route('reimbursements.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('reimbursements.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('reimbursements.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">wallet</span>
                    <span>{{ __('Reimbursement') }}</span>
                </a>

                <a href="{{ route('payroll.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('payroll.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('payroll.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">payments</span>
                    <span>{{ __('Payroll') }}</span>
                </a>

                <a href="{{ route('approvals.index') }}"
                   @class(['flex h-12 items-center gap-3 px-4 text-sm font-medium transition-colors',
                           'bg-ink/5 text-ink' => request()->routeIs('approvals.*'),
                           'text-on-surface-variant hover:bg-ink/5 hover:text-ink' => !request()->routeIs('approvals.*')])
                   wire:navigate>
                    <span class="material-symbols-outlined text-2xl">approval</span>
                    <span>{{ __('Approvals') }}</span>
                </a>
            </nav>

            {{-- User --}}
            <div class="border-t border-outline-variant p-3">
                <x-desktop-user-menu />
            </div>
        </aside>

        <!-- ─── Mobile Header ─── -->
        <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-outline-variant bg-canvas px-4 lg:hidden">
            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
            <x-desktop-user-menu />
        </header>

        <!-- ─── Main Content ─── -->
        <div class="px-4 py-4 lg:ms-64 lg:px-6 lg:py-6">
            {{ $slot }}
        </div>

        {{-- Mobile Bottom Navigation --}}
        <x-bottom-nav />

        @persist('toast')
            <div
                x-data="toast"
                x-show="show"
                x-cloak
                x-transition
                class="fixed bottom-4 right-4 z-50 max-w-sm rounded-xl bg-ink px-6 py-4 text-sm text-white shadow-lg"
            >
                <p x-text="message"></p>
            </div>
        @endpersist

        @vite(['resources/js/app.js'])
    </body>
</html>
