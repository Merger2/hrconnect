<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-hairline bg-canvas">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Main')">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
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

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        <div id="pwa-install-banner" class="hidden fixed bottom-0 inset-x-0 p-4 bg-canvas border-t border-hairline shadow-lg z-50">
            <div class="flex items-center justify-between max-w-sm mx-auto">
                <div class="flex items-center gap-3">
                    <img src="/icon-192.svg" alt="HRConnect" class="size-10 rounded-lg" />
                    <div>
                        <p class="text-sm font-medium text-ink">Install HRConnect</p>
                        <p class="text-xs text-muted">Akses cepat dari layar utama</p>
                    </div>
                </div>
                <button onclick="installPwa()" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-on-primary">Install</button>
            </div>
        </div>

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/service-worker.js').catch(() => {});
                });
            }
        </script>

        @fluxScripts
    </body>
</html>
