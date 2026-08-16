<div class="profile-section__card profile-section__card--danger">
    <div class="profile-section__header">
        <div class="min-w-0">
            <h3 class="profile-section__title text-red-800">{{ __('Delete Account') }}</h3>
            <p class="profile-section__desc text-red-600/80">{{ __('Permanently delete your account.') }}</p>
        </div>
    </div>

    <div class="profile-section__body">
        <p class="mb-4 text-sm text-red-700">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>

        <button type="button"
            class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
            wire:click="confirmUserDeletion" wire:loading.attr="disabled">
            {{ __('Delete Account') }}
        </button>
    </div>

    {{-- Confirmation Modal --}}
    <x-overlays.modal wire:model.live="confirmingUserDeletion" maxWidth="md">
        <div class="p-6">
            <h3 class="text-lg font-bold text-red-800">{{ __('Delete Account') }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ __('Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}</p>

            <div class="mt-4">
                <input type="password"
                    class="block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm transition focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20"
                    autocomplete="current-password"
                    placeholder="{{ __('Password') }}"
                    x-ref="password"
                    wire:model="password"
                    wire:keydown.enter="deleteUser" />
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                    wire:click="$toggle('confirmingUserDeletion')" wire:loading.attr="disabled">
                    {{ __('Cancel') }}
                </button>
                <button type="button"
                    class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                    wire:click="deleteUser" wire:loading.attr="disabled">
                    {{ __('Delete Account') }}
                </button>
            </div>
        </div>
    </x-overlays.modal>
</div>
