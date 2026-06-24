<x-layouts::app.sidebar>
    <h1 class="mb-4 text-2xl font-semibold text-ink">{{ __('Apply Leave') }}</h1>
    <p class="mb-6 text-sm text-on-surface-variant">{{ __('Submit a new leave request') }}</p>

    <div class="mx-auto max-w-2xl">
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <fieldset>
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Leave Type') }}</label>
                    <select class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
                        <option value="annual">{{ __('Annual Leave') }}</option>
                        <option value="sick">{{ __('Sick Leave') }}</option>
                        <option value="personal">{{ __('Personal Leave') }}</option>
                    </select>
                </div>

                <div class="mb-5 grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Start Date') }}</label>
                        <input type="date" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('End Date') }}</label>
                        <input type="date" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                    </div>
                </div>

                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Reason') }}</label>
                    <textarea rows="4" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60" placeholder="{{ __('Describe your leave reason...') }}"></textarea>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('leaves.index') }}" class="inline-flex items-center justify-center rounded-xl border border-outline-variant bg-canvas px-6 py-2.5 text-sm font-semibold text-ink" wire:navigate>
                        {{ __('Cancel') }}
                    </a>
                    <button class="inline-flex items-center justify-center rounded-xl bg-ink px-6 py-2.5 text-sm font-semibold text-white">
                        {{ __('Submit Request') }}
                    </button>
                </div>
            </fieldset>
        </div>
    </div>
</x-layouts::app.sidebar>
