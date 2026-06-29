<x-layouts::app.sidebar>
    <div x-data="loansIndex()">
        {{-- Header --}}
        <div class="mb-3 flex flex-col gap-2.5 border-b border-outline-variant/50 pb-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <h1 class="truncate text-lg font-semibold tracking-tight text-ink">{{ __('Loan Management') }}</h1>
                <p class="text-sm text-on-surface-variant">{{ __('Employee loan applications and repayment tracking') }}</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <x-button variant="primary" @click="openCreateModal" icon="add">
                    {{ __('Apply Loan') }}
                </x-button>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="mb-3 rounded-xl border border-outline-variant bg-canvas p-2.5 shadow-sm">
            <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-2 xl:grid-cols-12">
                <div class="xl:col-span-2">
                    <label class="mb-1.5 block text-xs font-medium text-on-surface-variant">{{ __('Status') }}</label>
                    <select x-model="statusFilter" @change="fetchLoans()"
                            class="block w-full rounded-lg border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink ring-1 ring-inset ring-outline-variant focus:ring-2 focus:ring-inset focus:ring-ink">
                        <option value="">{{ __('All') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="approved">{{ __('Approved') }}</option>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="paid_off">{{ __('Paid Off') }}</option>
                        <option value="rejected">{{ __('Rejected') }}</option>
                        <option value="cancelled">{{ __('Cancelled') }}</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Summary stats --}}
        <dl class="mb-4 flex flex-wrap gap-2" x-show="!loading">
            <div class="rounded-lg border border-outline-variant bg-surface-container-low px-3 py-1.5">
                <dt class="text-xs font-semibold uppercase text-on-surface-variant">{{ __('Total') }}</dt>
                <dd class="text-sm font-bold text-ink" x-text="formatCurrency(summary.total)">0</dd>
            </div>
            <div class="rounded-lg border border-success/30 bg-success/10 px-3 py-1.5">
                <dt class="text-xs font-semibold uppercase text-success">{{ __('Active') }}</dt>
                <dd class="text-sm font-bold text-success" x-text="formatCurrency(summary.active)">0</dd>
            </div>
            <div class="rounded-lg border border-warning/30 bg-warning/10 px-3 py-1.5">
                <dt class="text-xs font-semibold uppercase text-warning">{{ __('Pending') }}</dt>
                <dd class="text-sm font-bold text-warning" x-text="formatCurrency(summary.pending)">0</dd>
            </div>
            <div class="rounded-lg border border-info/30 bg-info/10 px-3 py-1.5">
                <dt class="text-xs font-semibold uppercase text-info">{{ __('Paid Off') }}</dt>
                <dd class="text-sm font-bold text-info" x-text="formatCurrency(summary.paidOff)">0</dd>
            </div>
        </dl>

        {{-- Loading --}}
        <div x-show="loading" class="flex items-center justify-center gap-2 py-16 text-sm text-on-surface-variant">
            <span class="material-symbols-outlined animate-spin text-lg">progress_activity</span>
            {{ __('Memuat...') }}
        </div>

        {{-- Content Panel --}}
        <x-app.panel>
            {{-- Desktop Table --}}
            <div x-show="!loading" class="hidden overflow-x-auto lg:block">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-surface-dim text-on-surface-variant">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Employee') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Amount') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Tenor') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Installment') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <template x-for="loan in records" :key="loan.id">
                            <tr class="transition-colors hover:bg-surface-dim">
                                <td class="px-4 py-3 text-ink" x-text="loan.employee?.full_name || '-'"></td>
                                <td class="px-4 py-3 font-medium text-ink" x-text="formatCurrency(loan.amount)"></td>
                                <td class="px-4 py-3 text-ink" x-text="loan.tenor_months + ' bln'"></td>
                                <td class="px-4 py-3 text-ink" x-text="formatCurrency(loan.monthly_installment)"></td>
                                <td class="px-4 py-3">
                                    <x-status-badge x-show="loan.status === 'pending'" tone="warning" pill>{{ __('Pending') }}</x-status-badge>
                                    <x-status-badge x-show="loan.status === 'approved'" tone="info" pill>{{ __('Approved') }}</x-status-badge>
                                    <x-status-badge x-show="loan.status === 'active'" tone="success" pill>{{ __('Active') }}</x-status-badge>
                                    <x-status-badge x-show="loan.status === 'paid_off'" tone="zinc" pill>{{ __('Paid Off') }}</x-status-badge>
                                    <x-status-badge x-show="loan.status === 'rejected'" tone="error" pill>{{ __('Rejected') }}</x-status-badge>
                                    <x-status-badge x-show="loan.status === 'cancelled'" tone="zinc" pill>{{ __('Cancelled') }}</x-status-badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button x-show="loan.status === 'pending' || loan.status === 'approved'" @click="cancelLoan(loan.id)"
                                            class="rounded-lg px-2 py-1 text-xs font-medium text-error transition-colors hover:bg-error/5">
                                        {{ __('Cancel') }}
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <template x-if="records.length === 0">
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="material-symbols-outlined text-3xl text-on-surface-variant/30">account_balance</span>
                                        <p class="text-sm font-medium text-ink">{{ __('Belum ada pinjaman') }}</p>
                                        <p class="text-xs text-on-surface-variant">{{ __('Ajukan pinjaman untuk mulai') }}</p>
                                        <div class="mt-2">
                                            <x-button variant="primary" @click="openCreateModal" icon="add" size="sm">{{ __('Apply Loan') }}</x-button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div x-show="!loading" class="space-y-3 lg:hidden">
                <template x-for="loan in records" :key="loan.id">
                    <div class="user-list-card p-4">
                        <div class="mb-1 flex items-start justify-between">
                            <div>
                                <p class="text-sm font-semibold text-ink" x-text="loan.employee?.full_name || '-'"></p>
                                <div class="mt-0.5 space-x-2 text-xs text-on-surface-variant">
                                    <span x-text="formatCurrency(loan.amount)"></span>
                                    <span>·</span>
                                    <span x-text="loan.tenor_months + ' bln'"></span>
                                    <span>·</span>
                                    <span x-text="formatCurrency(loan.monthly_installment) + '/bln'"></span>
                                </div>
                            </div>
                            <x-status-badge x-show="loan.status === 'pending'" tone="warning" pill>{{ __('Pending') }}</x-status-badge>
                            <x-status-badge x-show="loan.status === 'approved'" tone="info" pill>{{ __('Approved') }}</x-status-badge>
                            <x-status-badge x-show="loan.status === 'active'" tone="success" pill>{{ __('Active') }}</x-status-badge>
                            <x-status-badge x-show="loan.status === 'paid_off'" tone="zinc" pill>{{ __('Paid Off') }}</x-status-badge>
                            <x-status-badge x-show="loan.status === 'rejected'" tone="error" pill>{{ __('Rejected') }}</x-status-badge>
                            <x-status-badge x-show="loan.status === 'cancelled'" tone="zinc" pill>{{ __('Cancelled') }}</x-status-badge>
                        </div>
                        <button x-show="loan.status === 'pending' || loan.status === 'approved'" @click="cancelLoan(loan.id)"
                                class="mt-2 rounded-lg px-2 py-1 text-xs font-medium text-error transition-colors hover:bg-error/5">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </template>
                <template x-if="records.length === 0">
                    <div class="flex flex-col items-center gap-2 py-12">
                        <span class="material-symbols-outlined text-3xl text-on-surface-variant/30">account_balance</span>
                        <p class="text-sm font-medium text-ink">{{ __('Belum ada pinjaman') }}</p>
                    </div>
                </template>
            </div>
        </x-app.panel>

        {{-- Create Modal --}}
        <template x-teleport="body">
            <div x-show="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center"
                 x-cloak x-trap.noscroll="showCreateModal">
                <div class="fixed inset-0 bg-black/40" @click="showCreateModal = false"></div>
                <div class="relative z-10 w-full max-w-lg rounded-2xl bg-canvas p-6 shadow-xl">
                    <h2 class="mb-4 text-lg font-semibold text-ink">{{ __('New Loan Application') }}</h2>

                    <div class="space-y-5">
                        <x-forms.input name="amount" label="{{ __('Amount (IDR)') }}" type="number" x-model="form.amount" min="1" required />

                        <div>
                            <x-forms.input name="interest_rate" label="{{ __('Interest Rate (%)') }}" type="number" x-model="form.interest_rate" min="0" step="0.01" />
                            <p class="mt-1.5 text-xs text-on-surface-variant">{{ __('Set 0 for interest-free loan') }}</p>
                        </div>

                        <x-forms.input name="tenor_months" label="{{ __('Tenor (months)') }}" type="number" x-model="form.tenor_months" min="1" max="120" required />
                    </div>

                    <x-sections.section-border />

                    <div class="mt-6 flex items-center justify-end gap-2">
                        <button @click="showCreateModal = false; formError = ''"
                                class="rounded-lg border border-outline-variant bg-canvas px-4 py-2 text-sm font-medium text-ink transition-colors hover:bg-surface-dim">
                            {{ __('Cancel') }}
                        </button>
                        <button @click="submitLoan" :disabled="submitting"
                                class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-on-primary transition-colors hover:bg-primary/90 disabled:opacity-50">
                            <span x-show="!submitting">{{ __('Submit') }}</span>
                            <span x-show="submitting" class="flex items-center gap-1">
                                <span class="material-symbols-outlined animate-spin text-sm">progress_activity</span>
                                {{ __('Mengirim...') }}
                            </span>
                        </button>
                    </div>
                    <p x-show="formError" class="mt-2 text-sm text-error" x-text="formError"></p>
                </div>
            </div>
        </template>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('loansIndex', () => ({
                records: [],
                statusFilter: '',
                loading: true,
                submitting: false,
                showCreateModal: false,
                formError: '',
                summary: { total: 0, active: 0, pending: 0, paidOff: 0 },
                form: { amount: '', interest_rate: 0, tenor_months: 12 },

                init() {
                    this.fetchLoans();
                },

                openCreateModal() {
                    this.form = { amount: '', interest_rate: 0, tenor_months: 12 };
                    this.formError = '';
                    this.showCreateModal = true;
                },

                async fetchLoans() {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams({ per_page: 50 });
                        if (this.statusFilter) params.set('status', this.statusFilter);
                        const res = await fetch(`/api/v1/loans?${params}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            this.records = json.data;
                            this.calcSummary();
                        }
                    } catch { /* silent */ }
                    finally { this.loading = false; }
                },

                calcSummary() {
                    const total = this.records.reduce((s, r) => s + (r.amount || 0), 0);
                    const active = this.records.filter(r => r.status === 'active').reduce((s, r) => s + (r.amount || 0), 0);
                    const pending = this.records.filter(r => r.status === 'pending').reduce((s, r) => s + (r.amount || 0), 0);
                    const paidOff = this.records.filter(r => r.status === 'paid_off').reduce((s, r) => s + (r.amount || 0), 0);
                    this.summary = { total, active, pending, paidOff };
                },

                async submitLoan() {
                    if (!this.form.amount || this.form.amount < 1) {
                        this.formError = '{{ __('Jumlah pinjaman harus diisi') }}';
                        return;
                    }
                    if (!this.form.tenor_months || this.form.tenor_months < 1) {
                        this.formError = '{{ __('Tenor harus diisi') }}';
                        return;
                    }
                    this.submitting = true;
                    this.formError = '';
                    try {
                        const res = await fetch('/api/v1/loans', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify(this.form),
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            Livewire.dispatch('toast', { variant: 'success', text: json.message || '{{ __('Berhasil') }}' });
                            this.showCreateModal = false;
                            this.fetchLoans();
                        } else {
                            this.formError = json.message || '{{ __('Gagal mengajukan pinjaman') }}';
                        }
                    } catch {
                        this.formError = '{{ __('Koneksi error') }}';
                    }
                    finally { this.submitting = false; }
                },

                async cancelLoan(id) {
                    if (!confirm('{{ __('Batalkan pengajuan pinjaman ini?') }}')) return;
                    try {
                        const res = await fetch(`/api/v1/loans/${id}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            Livewire.dispatch('toast', { variant: 'success', text: json.message || '{{ __('Dibatalkan') }}' });
                            this.fetchLoans();
                        } else {
                            Livewire.dispatch('toast', { variant: 'error', text: json.message || '{{ __('Gagal') }}' });
                        }
                    } catch {
                        Livewire.dispatch('toast', { variant: 'error', text: '{{ __('Koneksi error') }}' });
                    }
                },

                formatCurrency(val) {
                    return 'Rp ' + (val || 0).toLocaleString('id-ID');
                },
            }));
        });
    </script>
</x-layouts::app.sidebar>
