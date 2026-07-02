<x-layouts::app.sidebar>
    <div x-data="reimbursementApply()">
        <x-page-shell title="{{ __('Ajukan Klaim') }}" subtitle="{{ __('Kirim permintaan penggantian biaya') }}">
            <x-slot:actions>
                <x-button variant="secondary" href="{{ route('reimbursements.index') }}" wire:navigate icon="arrow_back">
                    {{ __('Kembali') }}
                </x-button>
            </x-slot:actions>

        <div class="mx-auto max-w-lg">
            <div class="mb-3 rounded-xl border border-outline-variant/50 bg-info/5 px-4 py-3 text-sm text-info">
                <span class="material-symbols-outlined align-middle text-base">info</span>
                {{ __('Klaim akan ditinjau oleh atasan dan Finance. Simpan bukti asli.') }}
            </div>

            <x-app.panel class="p-6">
                <div class="space-y-5">
                    <div>
                        <x-forms.label for="category_id" value="{{ __('Kategori') }}" />
                        <select x-model="form.category_id" id="category_id" class="mt-1.5 w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
                            <option value="">{{ __('Pilih kategori...') }}</option>
                            <template x-for="c in categories" :key="c.id">
                                <option :value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-forms.label for="amount" value="{{ __('Jumlah (Rp)') }}" />
                            <input type="number" x-model="form.amount" id="amount" placeholder="0" x-on:input="form.amount = Math.max(0, parseInt($el.value) || 0)" class="mt-1.5 w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                            <p x-show="form.amount > 0" class="mt-1 text-xs text-success" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(form.amount)"></p>
                        </div>
                        <div>
                            <x-forms.label for="expense_date" value="{{ __('Tanggal') }}" />
                            <input type="date" x-model="form.expense_date" id="expense_date" class="mt-1.5 w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" />
                        </div>
                    </div>

                    <div>
                        <x-forms.label for="description" value="{{ __('Keterangan') }}" />
                        <textarea x-model="form.description" id="description" rows="4" class="mt-1.5 w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink" placeholder="{{ __('Jelaskan keperluan pengeluaran...') }}"></textarea>
                    </div>

                    <div>
                        <x-forms.label for="receipt" value="{{ __('Bukti (foto/PDF)') }}" />
                        <input type="file" id="receipt" accept="image/*,.pdf" @change="handleFile($event)" class="mt-1.5 w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink file:mr-4 file:rounded-lg file:border-0 file:bg-ink file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white" />
                        <p x-show="form.receipt_name" class="mt-1 text-xs text-success" x-text="'{{ __('Terpilih:') }} ' + form.receipt_name"></p>
                    </div>

                    <div x-show="error" class="rounded-xl bg-error/10 px-4 py-3 text-sm text-error" x-text="error"></div>

                    <div class="flex items-center justify-end gap-3 border-t border-outline-variant/50 pt-4">
                        <x-button variant="secondary" href="{{ route('reimbursements.index') }}" wire:navigate>{{ __('Batal') }}</x-button>
                        <x-button variant="primary" @click="submit()" x-bind:disabled="submitting">
                            <span x-show="!submitting">{{ __('Kirim Klaim') }}</span>
                            <span x-show="submitting" class="material-symbols-outlined animate-spin">progress_activity</span>
                        </x-button>
                    </div>
                </div>
            </x-app.panel>
        </div>
        </x-page-shell>
    </div>
</x-layouts::app.sidebar>
