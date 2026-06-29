@props([
    'title' => __('Konfirmasi Password'),
    'content' => __('Untuk keamanan, harap konfirmasi password Anda untuk melanjutkan.'),
    'button' => __('Konfirmasi'),
])

@php
$confirmableId = md5($attributes->wire('then'));
@endphp

<span
    {{ $attributes->wire('then') }}
    x-data
    x-ref="span"
    x-on:click="$wire.startConfirmingPassword('{{ $confirmableId }}')"
    x-on:password-confirmed.window="setTimeout(() => $event.detail.id === '{{ $confirmableId }}' && $refs.span.dispatchEvent(new CustomEvent('then', { bubbles: false })), 250);"
>
    {{ $slot }}
</span>

@once
    <x-modal :show="false" max-width="md">
        <x-slot name="title">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-error">lock</span>
                <span>{{ $title }}</span>
            </div>
        </x-slot>

        <div>
            <p class="text-sm text-on-surface-variant">{{ $content }}</p>

            <div class="mt-4" x-data="{}" x-on:confirming-password.window="setTimeout(() => $refs.confirmable_password.focus(), 250)">
                <input type="password"
                    x-ref="confirmable_password"
                    wire:model="confirmablePassword"
                    wire:keydown.enter="confirmPassword"
                    placeholder="{{ __('Password') }}"
                    autocomplete="current-password"
                    class="h-10 w-full rounded-xl border border-outline-variant bg-canvas px-3 text-sm text-ink outline-none placeholder:text-on-surface-variant focus:border-ink focus:ring-1 focus:ring-ink"
                />
                @error('confirmable_password')
                    <p class="mt-2 text-sm text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <x-slot name="footer">
            <button wire:click="stopConfirmingPassword"
                class="rounded-xl border border-outline-variant bg-canvas px-5 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-surface-dim">
                {{ __('Batal') }}
            </button>
            <button wire:click="confirmPassword" wire:loading.attr="disabled"
                class="rounded-xl bg-ink px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:opacity-90 disabled:opacity-40">
                {{ $button }}
            </button>
        </x-slot>
    </x-modal>
@endonce
