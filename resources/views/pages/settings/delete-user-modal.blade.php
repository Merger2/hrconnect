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
    class="fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6"
    role="dialog"
    aria-modal="true"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="open = false" aria-hidden="true"></div>
    <div class="relative z-10 mx-auto w-full max-w-lg overflow-hidden rounded-xl bg-canvas shadow-xl"
        x-on:click.stop
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
        <div class="px-6 py-5">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-error/10">
                <span class="material-symbols-outlined text-3xl text-error">warning</span>
            </div>
            <h3 class="text-center text-lg font-semibold text-ink">{{ __('Hapus Akun') }}</h3>
            <p class="mt-2 text-center text-sm text-on-surface-variant">
                {{ __('Semua data Anda akan dihapus permanen. Masukkan kata sandi untuk konfirmasi.') }}
            </p>
        </div>
        <form method="POST" wire:submit="deleteUser">
            <div class="px-6 pb-2">
                <x-forms.label for="password" value="{{ __('Kata Sandi') }}" />
                <x-forms.input wire:model="password" type="password" class="mt-1.5 w-full" placeholder="••••••••" />
                <x-forms.error name="password" />
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-outline-variant/50 bg-surface-dim/30 px-6 py-4">
                <x-button variant="secondary" @click="open = false">{{ __('Batal') }}</x-button>
                <x-button type="submit" variant="danger">{{ __('Hapus Akun') }}</x-button>
            </div>
        </form>
    </div>
</div>
