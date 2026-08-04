<x-form-section submit="save">
    <x-slot name="title">
        {{ __('Notification Preferences') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Choose how you want to be notified about system alerts and other events.') }}
    </x-slot>

    <x-slot name="form">
        <div class="col-span-6 space-y-4">
            @foreach($preferences as $id => $data)
                <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-slate-50">
                    <div>
                        <h4 class="font-medium text-slate-900">
                            {{ Str::headline($data['event_key']) }}
                        </h4>
                        <p class="text-sm text-slate-500">
                            {{ __('Receive alerts across different channels.') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2">
                            <x-forms.checkbox wire:model="preferences.{{ $id }}.in_app" />
                            <span class="text-sm text-slate-700">{{ __('In-App') }}</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <x-forms.checkbox wire:model="preferences.{{ $id }}.email" />
                            <span class="text-sm text-slate-700">{{ __('Email') }}</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <x-forms.checkbox wire:model="preferences.{{ $id }}.whatsapp" />
                            <span class="text-sm text-slate-700">{{ __('WhatsApp') }}</span>
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-actions.action-message class="me-3" on="saved">
            {{ __('Saved.') }}
        </x-actions.action-message>

        <x-actions.button wire:loading.attr="disabled">
            {{ __('Save') }}
        </x-actions.button>
    </x-slot>
</x-form-section>
