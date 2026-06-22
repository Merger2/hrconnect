<x-layouts::app.sidebar>
    <flux:heading size="xl" level="1" class="mb-4">{{ __('New Reimbursement Claim') }}</flux:heading>
    <flux:subheading class="mb-6">{{ __('Submit a new reimbursement request') }}</flux:subheading>

    <div class="mx-auto max-w-2xl">
        <flux:card class="p-6">
            <flux:fieldset>
                <flux:field name="category">
                    <flux:label>{{ __('Category') }}</flux:label>
                    <flux:select>
                        <option value="travel">{{ __('Travel') }}</option>
                        <option value="medical">{{ __('Medical') }}</option>
                        <option value="supplies">{{ __('Office Supplies') }}</option>
                        <option value="training">{{ __('Training / Education') }}</option>
                        <option value="other">{{ __('Other') }}</option>
                    </flux:select>
                </flux:field>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <flux:field name="amount">
                        <flux:label>{{ __('Amount (IDR)') }}</flux:label>
                        <flux:input type="number" placeholder="0" />
                    </flux:field>
                    <flux:field name="expense_date">
                        <flux:label>{{ __('Expense Date') }}</flux:label>
                        <flux:input type="date" />
                    </flux:field>
                </div>

                <flux:field name="description" class="mt-4">
                    <flux:label>{{ __('Description') }}</flux:label>
                    <flux:textarea rows="4" placeholder="{{ __('Describe the expense...') }}" />
                </flux:field>

                <flux:field name="receipt" class="mt-4">
                    <flux:label>{{ __('Receipt (optional)') }}</flux:label>
                    <flux:input type="file" accept="image/*,.pdf" />
                </flux:field>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <flux:button href="{{ route('reimbursements.index') }}" variant="ghost" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary">
                        {{ __('Submit Claim') }}
                    </flux:button>
                </div>
            </flux:fieldset>
        </flux:card>
    </div>
</x-layouts::app.sidebar>
