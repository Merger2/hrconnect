<x-layouts::app.sidebar>
    <h1 class="mb-4 text-2xl font-semibold text-ink">{{ __('Request Overtime') }}</h1>
    <p class="mb-6 text-sm text-on-surface-variant">{{ __('Submit a new overtime request') }}</p>

    <div class="mx-auto max-w-2xl">
        <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-6">
            <fieldset>
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Date') }}</label>
                    <input type="date" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                </div>

                <div class="mb-5 grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Start Time') }}</label>
                        <input type="time" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('End Time') }}</label>
                        <input type="time" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                    </div>
                </div>

                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Reason') }}</label>
                    <textarea rows="4" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60" placeholder="{{ __('Describe the reason for overtime...') }}"></textarea>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('overtimes.index') }}" class="inline-flex items-center justify-center rounded-xl border border-outline-variant bg-canvas px-6 py-2.5 text-sm font-semibold text-ink" wire:navigate>
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
