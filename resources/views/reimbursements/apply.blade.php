<x-layouts::app.sidebar>
    <h1 class="mb-4 text-2xl font-semibold text-ink">{{ __('New Reimbursement Claim') }}</h1>
    <p class="mb-6 text-sm text-on-surface-variant">{{ __('Submit a new reimbursement request') }}</p>

    <div class="mx-auto max-w-2xl">
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <fieldset>
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Category') }}</label>
                    <select class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
                        <option value="travel">{{ __('Travel') }}</option>
                        <option value="medical">{{ __('Medical') }}</option>
                        <option value="supplies">{{ __('Office Supplies') }}</option>
                        <option value="training">{{ __('Training / Education') }}</option>
                        <option value="other">{{ __('Other') }}</option>
                    </select>
                </div>

                <div class="mb-5 grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Amount (IDR)') }}</label>
                        <input type="number" placeholder="0" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Expense Date') }}</label>
                        <input type="date" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                    </div>
                </div>

                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Description') }}</label>
                    <textarea rows="4" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60" placeholder="{{ __('Describe the expense...') }}"></textarea>
                </div>

                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Receipt (optional)') }}</label>
                    <input type="file" accept="image/*,.pdf" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink file:mr-4 file:rounded-lg file:border-0 file:bg-ink file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white" />
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('reimbursements.index') }}" class="inline-flex items-center justify-center rounded-xl border border-outline-variant bg-canvas px-6 py-2.5 text-sm font-semibold text-ink" wire:navigate>
                        {{ __('Cancel') }}
                    </a>
                    <button class="inline-flex items-center justify-center rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white">
                        {{ __('Submit Claim') }}
                    </button>
                </div>
            </fieldset>
        </div>
    </div>
</x-layouts::app.sidebar>
