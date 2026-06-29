<x-layouts::app.sidebar>
    <div x-data="reimbursementApply()">
        {{-- Header --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-ink">{{ __('New Reimbursement Claim') }}</h1>
                <p class="mt-1 text-sm text-on-surface-variant">{{ __('Submit a new reimbursement request') }}</p>
            </div>
            <x-button variant="secondary" href="{{ route('reimbursements.index') }}" wire:navigate icon="arrow_back">
                {{ __('Back') }}
            </x-button>
        </div>

        <div class="mx-auto max-w-2xl">
            <x-app.panel class="p-6">
                {{-- Category --}}
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Category') }}</label>
                    <select x-model="form.category_id" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
                        <option value="">{{ __('Pilih kategori...') }}</option>
                        <template x-for="c in categories" :key="c.id">
                            <option :value="c.id" x-text="c.name"></option>
                        </template>
                    </select>
                </div>

                {{-- Amount + Date --}}
                <div class="mb-5 grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Amount (IDR)') }}</label>
                        <input type="number" x-model="form.amount" placeholder="0" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Expense Date') }}</label>
                        <input type="date" x-model="form.expense_date" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                    </div>
                </div>

                {{-- Description --}}
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Description') }}</label>
                    <textarea x-model="form.description" rows="4" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink placeholder:text-on-surface-variant/60" placeholder="{{ __('Describe the expense...') }}"></textarea>
                </div>

                {{-- Receipt --}}
                <div class="mb-5">
                    <label class="mb-1 block text-sm font-medium text-on-surface-variant">{{ __('Receipt') }}</label>
                    <input type="file" accept="image/*,.pdf" @change="handleFile($event)" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink file:mr-4 file:rounded-lg file:border-0 file:bg-ink file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white" />
                    <p x-show="form.receipt_name" class="mt-1 text-xs text-on-surface-variant" x-text="form.receipt_name"></p>
                </div>

                {{-- Error --}}
                <div x-show="error" class="mb-4 rounded-xl bg-error/10 p-4 text-sm text-error" x-text="error"></div>

                {{-- Actions --}}
                <div class="mt-6 flex items-center justify-end gap-3">
                    <x-button variant="secondary" href="{{ route('reimbursements.index') }}" wire:navigate>{{ __('Cancel') }}</x-button>
                    <x-button variant="primary" @click="submit()" x-bind:disabled="submitting">
                        <span x-show="!submitting">{{ __('Submit Claim') }}</span>
                        <span x-show="submitting" class="material-symbols-outlined animate-spin">progress_activity</span>
                    </x-button>
                </div>
            </x-app.panel>
        </div>
    </div>


</x-layouts::app.sidebar>
