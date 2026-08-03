<x-app-layout>
    <div class="profile-page user-page-shell">
        <div class="user-page-container user-page-container--wide">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('Profile')"
                title-id="profile-page-title">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </x-slot>
            </x-user.page-header>

            <section aria-labelledby="profile-page-title" class="user-page-surface">

                {{-- Profile Identity Card — redesigned --}}
                <div class="profile-identity">
                    <div class="profile-identity__avatar-shell">
                        <img class="profile-identity__avatar"
                             src="{{ auth()->user()->profile_photo_url }}"
                             alt="{{ auth()->user()->name }}" />
                        @if(auth()->user()->hasFaceRegistered())
                            <span class="profile-identity__badge" title="{{ __('Face ID Active') }}">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                                </svg>
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
                @unless (\App\Helpers\Editions::attendanceLocked())
                    <a href="{{ route('face.enrollment') }}"
                       class="profile-section-nav__link mb-4">
                        <span class="profile-section-nav__icon">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="profile-section-nav__title">{{ __('Face ID') }}</span>
                            <span class="profile-section-nav__copy">
                                {{ auth()->user()->hasFaceRegistered()
                                    ? __('Face ID is ready to use.')
                                    : __('Register your face for secure attendance.') }}
                            </span>
                        </span>
                        <svg class="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @endunless

                {{-- Profile Forms — redesigned with modern styling --}}
                <div class="space-y-4">
                    @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                        <div class="profile-section">
                            @livewire('profile.update-profile-information-form')
                        </div>
                    @endif

                    @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                        <div class="profile-section">
                            @livewire('profile.update-password-form')
                        </div>
                    @endif

                    @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                        <div class="profile-section">
                            @livewire('profile.two-factor-authentication-form')
                        </div>
                    @endif

                    <div class="profile-section">
                        @livewire('profile.logout-other-browser-sessions-form')
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
