<x-app-layout>
    <div class="profile-page user-page-shell">
        <div class="user-page-container user-page-container--wide">
            <section aria-labelledby="profile-page-title" class="user-page-surface">
                <x-user.page-header
                    :back-href="route('home')"
                    :title="__('Profile')"
                    title-id="profile-page-title"
                    module="hr"
                    class="border-b-0">
                    <x-slot name="icon">
                        <x-heroicon-o-user-circle class="h-5 w-5" />
                    </x-slot>
                </x-user.page-header>

                {{-- Profile Identity Card — redesigned --}}
                <div class="profile-identity user-accent-card user-accent-card--hr">
                    <div class="profile-identity__avatar-shell">
                        <img class="profile-identity__avatar"
                             src="{{ auth()->user()->profile_photo_url }}"
                             alt="{{ auth()->user()->name }}" />
                        @if(auth()->user()->hasFaceRegistered())
                            <span class="profile-identity__badge" title="{{ __('Face ID Active') }}">
                                <x-heroicon-o-face-smile class="h-3.5 w-3.5" />
                            </span>
                        @endif
                    </div>
                    <div class="profile-identity__content">
                        <p class="profile-identity__eyebrow">{{ __('Employee') }}</p>
                        <h2 class="profile-identity__name">{{ auth()->user()->name }}</h2>
                        <p class="profile-identity__email">{{ auth()->user()->email }}</p>
                        @if(auth()->user()->employee?->division?->name)
                            <div class="profile-identity__meta">
                                <span>{{ auth()->user()->employee->division->name }}</span>
                                @if(auth()->user()->employee?->jobTitle?->name)
                                    <span>{{ auth()->user()->employee->jobTitle->name }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Stats row --}}
                <div class="profile-stats">
                    <div class="profile-stat">
                        <span class="profile-stat__value">{{ auth()->user()->employee?->leaveBalances()->sum('quota') ?? '—' }}</span>
                        <span class="profile-stat__label">{{ __('Leave Days') }}</span>
                    </div>
                    <div class="profile-stat">
                        <span class="profile-stat__value">{{ auth()->user()->employee?->attendances()->whereDate('date', today())->exists() ? '✓' : '—' }}</span>
                        <span class="profile-stat__label">{{ __('Today') }}</span>
                    </div>
                    <div class="profile-stat">
                        <span class="profile-stat__value">{{ auth()->user()->employee?->faceDescriptors()->exists() ? '✓' : '—' }}</span>
                        <span class="profile-stat__label">{{ __('Face ID') }}</span>
                    </div>
                </div>

                {{-- Face ID nav --}}
                <a href="{{ route('face.enrollment') }}"
                   class="profile-section-nav__link mb-4">
                        <span class="profile-section-nav__icon">
                            <x-heroicon-o-face-smile class="h-5 w-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="profile-section-nav__title">{{ __('Face ID') }}</span>
                            <span class="profile-section-nav__copy">
                                {{ auth()->user()->hasFaceRegistered()
                                    ? __('Face ID is ready to use.')
                                    : __('Register your face for secure attendance.') }}
                            </span>
                        </span>
                        <x-heroicon-o-chevron-right class="h-5 w-5 shrink-0 text-slate-400" />
                    </a>

                {{-- Profile Forms — redesigned with modern styling --}}
                <div class="space-y-4">
                    @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                        <div class="profile-section user-accent-card user-accent-card--hr">
                            @livewire('profile.update-profile-information-form')
                        </div>
                    @endif

                    @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                        <div class="profile-section user-accent-card user-accent-card--hr">
                            @livewire('profile.update-password-form')
                        </div>
                    @endif

                    @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                        <div class="profile-section user-accent-card user-accent-card--hr">
                            @livewire('profile.two-factor-authentication-form')
                        </div>
                    @endif

                    <div class="profile-section user-accent-card user-accent-card--hr">
                        @livewire('profile.logout-other-browser-sessions-form')
                    </div>

                    {{-- Log Out (sesi sendiri) — 2026-08-16: employee tidak punya tombol
                         logout di nav; ditambahkan di profil agar selalu terjangkau. --}}
                    <div class="profile-section user-accent-card user-accent-card--hr">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-slate-900">{{ __('Log Out') }}</h3>
                                <p class="mt-1 text-sm text-slate-600">{{ __('End this session on this device.') }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" x-data>
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center rounded-lg bg-primary-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2">
                                    <x-heroicon-o-arrow-right-on-rectangle class="me-2 h-4 w-4" />
                                    {{ __('Log Out') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures() && auth()->user()->can('delete', \App\Models\User::class))
                        <div class="profile-section profile-section--danger">
                            @livewire('profile.delete-user-form')
                        </div>
                    @endif
                </div>

            </section>
        </div>
    </div>
</x-app-layout>
