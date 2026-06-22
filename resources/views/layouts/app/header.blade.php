<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas">
        <flux:header container class="border-b border-hairline bg-canvas">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-hairline bg-canvas">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Main')">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('HR')">
                    <flux:sidebar.item icon="clock" :href="route('attendance.index')" :current="request()->routeIs('attendance.*')" wire:navigate>
                        {{ __('Attendance') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calendar" :href="route('leaves.index')" :current="request()->routeIs('leaves.*')" wire:navigate>
                        {{ __('Leave') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="bolt" :href="route('overtimes.index')" :current="request()->routeIs('overtimes.*')" wire:navigate>
                        {{ __('Overtime') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="wallet" :href="route('reimbursements.index')" :current="request()->routeIs('reimbursements.*')" wire:navigate>
                        {{ __('Reimbursement') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
