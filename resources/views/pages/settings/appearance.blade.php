<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <h2 class="sr-only">{{ __('Appearance settings') }}</h2>

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div class="flex gap-2" x-data="{ mode: localStorage.getItem('theme') || 'system' }" x-init="$watch('mode', val => { localStorage.setItem('theme', val); document.documentElement.classList.toggle('dark', val === 'dark' || (val === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)); })">
            <button @click="mode = 'light'" :class="mode === 'light' ? 'bg-ink text-white' : 'bg-surface-container-low text-ink'" class="flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold">
                <span class="material-symbols-outlined text-base">light_mode</span>
                {{ __('Light') }}
            </button>
            <button @click="mode = 'dark'" :class="mode === 'dark' ? 'bg-ink text-white' : 'bg-surface-container-low text-ink'" class="flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold">
                <span class="material-symbols-outlined text-base">dark_mode</span>
                {{ __('Dark') }}
            </button>
            <button @click="mode = 'system'" :class="mode === 'system' ? 'bg-ink text-white' : 'bg-surface-container-low text-ink'" class="flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold">
                <span class="material-symbols-outlined text-base">settings_suggest</span>
                {{ __('System') }}
            </button>
        </div>
    </x-pages::settings.layout>
</section>
