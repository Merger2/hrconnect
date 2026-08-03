<div class="profile-section__card">
    <div class="profile-section__header">
        <div class="min-w-0">
            <h3 class="profile-section__title">{{ __('Browser Sessions') }}</h3>
            <p class="profile-section__desc">{{ __('Manage and log out your active sessions on other browsers and devices.') }}</p>
        </div>
    </div>

    <div class="profile-section__body">
        <p class="mb-4 text-sm text-slate-600">
            {{ __('If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.') }}
        </p>

        @if (count($this->sessions) > 0)
            <div class="mb-5 space-y-3">
                @foreach ($this->sessions as $session)
                    <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/50 px-4 py-3">
                        <div class="shrink-0 text-slate-400">
                            @if ($session->agent->isDesktop())
                                <x-heroicon-o-computer-desktop class="h-6 w-6" />
                            @else
                                <x-heroicon-o-device-phone-mobile class="h-6 w-6" />
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-800">
                                {{ $session->agent->platform() ?: __('Unknown') }} - {{ $session->agent->browser() ?: __('Unknown') }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ $session->ip_address }}
                                @if ($session->is_current_device)
                                    <span class="ml-1 font-semibold text-emerald-600">({{ __('This device') }})</span>
                                @else
                                    <span class="ml-1">&middot; {{ __('Last active') }} {{ $session->last_active }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="flex items-center gap-3">
            <button type="button"
                class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                wire:click="confirmLogout" wire:loading.attr="disabled">
                {{ __('Log Out Other Browser Sessions') }}
            </button>
            <div x-data="{ shown: false, timeout: null }"
                 x-init="window.Livewire.find('{{ $__livewire->getId() }}').on('loggedOut', () => { clearTimeout(timeout); shown = true; timeout = setTimeout(() => { shown = false }, 2000); })"
                 x-show.transition.out.opacity.duration.1500ms="shown"
                 x-cloak
                 class="text-sm font-medium text-emerald-600">
                {{ __('Done.') }}
            </div>
        </div>
    </div>

    {{-- Confirmation Modal --}}
    <div x-data="{ show: window.Livewire.find('{{ $__livewire->getId() }}').entangle('confirmingLogout').live }"
         x-show="show" x-cloak
         class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 sm:items-center"
         @keydown.escape.window="show = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         role="dialog" aria-modal="true">
        <div x-show="show" x-cloak
             @click.away="show = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             class="w-full max-w-md rounded-t-2xl bg-white p-6 shadow-2xl sm:rounded-2xl">
            <h3 class="text-lg font-bold text-slate-900">{{ __('Log Out Other Browser Sessions') }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ __('Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.') }}</p>

            <div class="mt-4">
                <input type="password"
                    class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm transition focus:border-primary-500 focus:bg-white focus:ring-2 focus:ring-primary-500/20"
                    autocomplete="current-password"
                    placeholder="{{ __('Password') }}"
                    x-ref="password"
                    wire:model="password"
                    wire:keydown.enter="logoutOtherBrowserSessions" />
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                    wire:click="$toggle('confirmingLogout')" wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </button>
                <button type="button"
                    class="rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    wire:click="logoutOtherBrowserSessions" wire:loading.attr="disabled">
                    {{ __('Log Out Other Browser Sessions') }}
                </button>
            </div>
        </div>
    </div>
</div>
