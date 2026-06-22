<x-layouts::app.sidebar>
    <flux:heading size="xl" level="1" class="mb-4">{{ __('Apply Leave') }}</flux:heading>
    <flux:subheading class="mb-6">{{ __('Submit a new leave request') }}</flux:subheading>

    <div class="mx-auto max-w-2xl">
        <flux:card class="p-6">
            <flux:fieldset>
                <flux:field name="leave_type">
                    <flux:label>{{ __('Leave Type') }}</flux:label>
                    <flux:select>
                        <option value="annual">{{ __('Annual Leave') }}</option>
                        <option value="sick">{{ __('Sick Leave') }}</option>
                        <option value="personal">{{ __('Personal Leave') }}</option>
                    </flux:select>
                </flux:field>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <flux:field name="start_date">
                        <flux:label>{{ __('Start Date') }}</flux:label>
                        <flux:input type="date" />
                    </flux:field>
                    <flux:field name="end_date">
                        <flux:label>{{ __('End Date') }}</flux:label>
                        <flux:input type="date" />
                    </flux:field>
                </div>

                <flux:field name="reason" class="mt-4">
                    <flux:label>{{ __('Reason') }}</flux:label>
                    <flux:textarea rows="4" placeholder="{{ __('Describe your leave reason...') }}" />
                </flux:field>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <flux:button href="{{ route('leaves.index') }}" variant="ghost" wire:navigate>
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
