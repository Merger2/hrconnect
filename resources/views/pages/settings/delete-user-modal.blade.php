<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div
    x-data="{ open: false }"
    x-show="open"
    x-cloak
    @open-modal.window="if ($event.detail === 'confirm-user-deletion') open = true"
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-50 flex items-center justify-center"
    role="dialog"
    aria-modal="true"
>
    <div class="fixed inset-0 bg-black/40" @click="open = false"></div>
    <div class="relative z-10 w-full max-w-lg rounded-lg bg-canvas p-6 shadow-xl">
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <div>
                <h2 class="text-lg font-semibold text-ink">{{ __('Are you sure you want to delete your account?') }}</h2>
                <p class="mt-1 text-sm text-on-surface-variant">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-on-background">{{ __('Password') }}</label>
                <input
                    wire:model="password"
                    type="password"
                    class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60 focus:border-ink focus:ring-1 focus:ring-ink"
                />
                @error('password')
                    <p class="mt-1 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-2">
                <button @click="open = false" type="button" class="rounded-xl border border-outline-variant bg-canvas px-6 py-2.5 text-sm font-semibold text-ink">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="rounded-xl bg-error px-6 py-2.5 text-sm font-semibold text-white" data-test="confirm-delete-user-button">
                    {{ __('Delete account') }}
                </button>
            </div>
        </form>
    </div>
</div>
