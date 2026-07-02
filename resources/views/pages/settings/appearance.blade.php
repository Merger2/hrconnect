<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Pengaturan Tampilan')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Tampilan')" :subheading="__('Mode terang dan gelap akan segera tersedia')">
        <div class="rounded-xl border border-outline-variant bg-canvas p-6 text-center">
            <span class="material-symbols-outlined text-4xl text-on-surface-variant/40">palette</span>
            <p class="mt-2 text-sm text-on-surface-variant">{{ __('Fitur tema sedang dalam pengembangan.') }}</p>
        </div>
    </x-pages::settings.layout>
</section>
