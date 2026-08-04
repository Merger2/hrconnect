<x-app-layout>
    @php
        $user = auth()->user();
        $backRouteName = $user->preferredAdminRouteName() ?? 'home';
        $profileSections = [];

        if (Laravel\Fortify\Features::canUpdateProfileInformation()) {
            $profileSections['details'] = [
                'title' => __('Profile Information'),
                'description' => __('Update your profile photo, contact information, and address.'),
            ];
        }

        if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords())) {
            $profileSections['password'] = [
                'title' => __('Password'),
                'description' => __('Change the password used to access the admin panel.'),
            ];
        }

        if (Laravel\Fortify\Features::canManageTwoFactorAuthentication()) {
            $profileSections['security'] = [
                'title' => __('Two Factor Authentication'),
                'description' => __('Manage the second factor for this administrator account.'),
            ];
        }

        $profileSections['sessions'] = [
            'title' => __('Browser Sessions'),
            'description' => __('Review active sessions and sign out from other devices.'),
        ];

        $profileSections['activity'] = [
            'title' => __('Activity Logs'),
            'description' => __('Review your recent administrative actions.'),
        ];

        $profileSections['notifications'] = [
            'title' => __('Notifications'),
            'description' => __('Manage alert and notification channels.'),
        ];

        if (Laravel\Jetstream\Jetstream::hasApiFeatures()) {
            $profileSections['api'] = [
                'title' => __('API Tokens'),
                'description' => __('Manage API tokens for external integrations.'),
            ];
        }

        $defaultProfileTab = array_key_first($profileSections);
        $profileTabKeys = array_keys($profileSections);
    @endphp

    <x-admin.page-shell
        :title="__('Admin Profile')"
        :description="__('Manage your administrator account, security, and preferences.')"
        x-data="{
            tabs: {{ json_encode($profileTabKeys) }},
            activeTab: '{{ $defaultProfileTab }}',
            init() {
                const hash = window.location.hash.slice(1);
                if (this.tabs.includes(hash)) {
                    this.activeTab = hash;
                }
            },
            setTab(tab) {
                this.activeTab = tab;
                window.history.replaceState({}, '', '#' + tab);
            },
        }"
    >
        <x-slot name="toolbar">
            <x-admin.page-tools grid-class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <img class="h-12 w-12 rounded-full object-cover ring-2 ring-white shadow-sm" src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
                        <div class="min-w-0">
                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                <h2 class="truncate text-sm font-semibold text-slate-950">{{ $user->name }}</h2>
                                <x-admin.status-badge tone="info" pill="true">{{ $user->isSuperadmin ? __('Super Admin') : __('Admin') }}</x-admin.status-badge>
                                <x-admin.status-badge :tone="$user->hasVerifiedEmail() ? 'success' : 'warning'" pill="true">{{ $user->hasVerifiedEmail() ? __('Verified') : __('Unverified') }}</x-admin.status-badge>
                            </div>
                            <p class="mt-0.5 truncate text-sm text-slate-600">{{ $user->email }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                    <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-3 py-2">
                        <span class="text-xs font-semibold uppercase text-slate-400">{{ __('Language') }}</span>
                        <form method="POST" action="{{ route('user.language.update') }}">
                            @csrf
                            <input type="hidden" name="language" value="{{ app()->getLocale() == 'id' ? 'en' : 'id' }}">
                            <button type="submit"
                                class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                aria-label="{{ __('Switch language to :language', ['language' => app()->getLocale() == 'id' ? 'English' : 'Bahasa Indonesia']) }}">
                                <span class="sr-only">{{ __('Switch Language') }}</span>
                                <span class="absolute inset-0 rounded-full {{ app()->getLocale() == 'id' ? 'bg-primary' : 'bg-slate-200' }}"></span>
                                <span class="relative z-10 flex w-full items-center justify-between px-2 text-[10px] font-semibold uppercase tracking-wider">
                                    <span class="{{ app()->getLocale() == 'id' ? 'text-white' : 'text-slate-500' }}">ID</span>
                                    <span class="{{ app()->getLocale() == 'en' ? 'text-white' : 'text-slate-500' }}">EN</span>
                                </span>
                                <span class="absolute top-0.5 h-6 w-6 rounded-full bg-white shadow-md transition-transform duration-150 {{ app()->getLocale() == 'en' ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                            </button>
                        </form>
                    </div>
                </div>

            </x-admin.page-tools>
        </x-slot>

        <div class="grid gap-4 lg:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="space-y-2">
                @foreach ($profileSections as $sectionKey => $section)
                    <button type="button" class="w-full rounded-xl border px-4 py-3 text-left transition"
                        x-on:click="setTab('{{ $sectionKey }}')"
                        x-bind:class="activeTab === '{{ $sectionKey }}' ?
                            'border-primary-300 bg-primary-50 text-primary-900 shadow-sm' :
                            'border-slate-200 bg-white text-slate-700 hover:border-slate-300'">
                        <span class="block text-sm font-semibold">{{ $section['title'] }}</span>
                        <span
                            class="mt-1 block text-xs leading-5 text-slate-500">{{ $section['description'] }}</span>
                    </button>
                @endforeach
            </aside>

            <div class="min-w-0 space-y-4">
                @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                    <section x-cloak x-show="activeTab === 'details'" x-transition.opacity.duration.150ms>
                        <livewire:profile.update-profile-information-form />
                    </section>
                @endif

                @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                    <section x-cloak x-show="activeTab === 'password'" x-transition.opacity.duration.150ms>
                        <livewire:profile.update-password-form />
                    </section>
                @endif

                @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                    <section x-cloak x-show="activeTab === 'security'" x-transition.opacity.duration.150ms>
                        <livewire:profile.two-factor-authentication-form />
                    </section>
                @endif

                <section x-cloak x-show="activeTab === 'sessions'" x-transition.opacity.duration.150ms>
                    <livewire:profile.logout-other-browser-sessions-form />
                </section>

                <section x-cloak x-show="activeTab === 'activity'" x-transition.opacity.duration.150ms>
                    <livewire:admin.profile.activity-log-viewer />
                </section>

                <section x-cloak x-show="activeTab === 'notifications'" x-transition.opacity.duration.150ms>
                    <livewire:admin.profile.notification-preferences-form />
                </section>

                @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                    <section x-cloak x-show="activeTab === 'api'" x-transition.opacity.duration.150ms>
                        <livewire:api.api-token-manager />
                    </section>
                @endif
            </div>
        </div>
    </x-admin.page-shell>
</x-app-layout>
