<x-layouts::app.sidebar>
    <flux:heading size="xl" level="1" class="mb-4">{{ __('Request Overtime') }}</flux:heading>
    <flux:subheading class="mb-6">{{ __('Submit a new overtime request') }}</flux:subheading>

    <div class="mx-auto max-w-2xl">
        <flux:card class="p-6">
            <flux:fieldset>
                <flux:field name="date">
                    <flux:label>{{ __('Date') }}</flux:label>
                    <flux:input type="date" />
                </flux:field>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <flux:field name="start_time">
                        <flux:label>{{ __('Start Time') }}</flux:label>
                        <flux:input type="time" />
                    </flux:field>
                    <flux:field name="end_time">
                        <flux:label>{{ __('End Time') }}</flux:label>
                        <flux:input type="time" />
                    </flux:field>
                </div>

                <flux:field name="reason" class="mt-4">
                    <flux:label>{{ __('Reason') }}</flux:label>
                    <flux:textarea rows="4" placeholder="{{ __('Describe the reason for overtime...') }}" />
                </flux:field>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <flux:button href="{{ route('overtimes.index') }}" variant="ghost" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary">
                        {{ __('Submit Request') }}
                    </flux:button>
                </div>
            </flux:fieldset>
        </flux:card>
    </div>
</x-layouts::app.sidebar>
