<div class="profile-section__card">
    <div class="profile-section__header">
        <div class="min-w-0">
            <h3 class="profile-section__title">{{ __('Update Password') }}</h3>
            <p class="profile-section__desc">{{ __('Ensure your account is using a long, random password to stay secure.') }}</p>
        </div>
    </div>

    <form wire:submit="updatePassword" class="profile-section__body">
        <div class="mb-4">
            <label class="profile-field__label" for="current_password">{{ __('Current Password') }}</label>
            <input id="current_password" type="password"
                class="profile-field__input mt-1 block w-full"
                wire:model="state.current_password" autocomplete="current-password" />
            @error('current_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="profile-field__label" for="password">{{ __('New Password') }}</label>
            <input id="password" type="password"
                class="profile-field__input mt-1 block w-full"
                wire:model="state.password" autocomplete="new-password" />
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="profile-field__label" for="password_confirmation">{{ __('Confirm Password') }}</label>
            <input id="password_confirmation" type="password"
                class="profile-field__input mt-1 block w-full"
                wire:model="state.password_confirmation" autocomplete="new-password" />
            @error('password_confirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
            <div x-data="{ shown: false, timeout: null }"
                 x-init="window.Livewire.find('{{ $__livewire->getId() }}').on('saved', () => { clearTimeout(timeout); shown = true; timeout = setTimeout(() => { shown = false }, 2000); })"
                 x-show.transition.out.opacity.duration.1500ms="shown"
                 x-cloak
                 class="text-sm font-medium text-emerald-600">
                {{ __('Saved.') }}
            </div>
            <button type="submit"
                class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                {{ __('Save') }}
            </button>
        </div>
    </form>
</div>
