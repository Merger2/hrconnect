<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h2 class="text-lg font-semibold text-ink">{{ __('Delete account') }}</h2>
        <p class="text-sm text-on-surface-variant">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <button
        class="rounded-xl bg-error px-6 py-2.5 text-sm font-semibold text-white"
        x-data
        @click="$dispatch('open-modal', 'confirm-user-deletion')"
        data-test="delete-user-button"
    >
        {{ __('Hapus Akun') }}
    </button>

    <livewire:pages::settings.delete-user-modal />
</section>
