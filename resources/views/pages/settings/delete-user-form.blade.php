<?php

use Livewire\Component;

new class extends Component {}; ?>

<section>
    <x-button
        variant="danger"
        x-data
        @click="$dispatch('open-modal', 'confirm-user-deletion')"
        data-test="delete-user-button"
    >
        {{ __('Hapus Akun') }}
    </x-button>

    <livewire:pages::settings.delete-user-modal />
</section>
