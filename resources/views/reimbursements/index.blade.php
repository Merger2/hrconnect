<x-layouts::app.sidebar>
    <div x-data="reimbursementsIndex()">
        {{-- Header --}}
        <div class="mb-3 flex flex-col gap-2.5 border-b border-outline-variant/50 pb-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <h1 class="truncate text-lg font-semibold tracking-tight text-ink">{{ __('Reimbursements') }}</h1>
                <p class="text-sm text-on-surface-variant">{{ __('View and manage your reimbursement claims') }}</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <x-button variant="primary" href="{{ route('reimbursements.apply') }}" wire:navigate icon="add">
                    {{ __('New Claim') }}
                </x-button>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="mb-3 rounded-xl border border-outline-variant bg-canvas p-2.5 shadow-sm">
            <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-2 xl:grid-cols-12">
                <div class="xl:col-span-2">
                    <label class="mb-1.5 block text-xs font-medium text-on-surface-variant">{{ __('Period') }}</label>
                    <select x-model="period" @change="fetchReimbursements()"
                            class="block w-full rounded-lg border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink ring-1 ring-inset ring-outline-variant focus:ring-2 focus:ring-inset focus:ring-ink">
                        <option :value="currentPeriod()">{{ __('This Month') }}</option>
                        <option :value="lastMonthPeriod()">{{ __('Last Month') }}</option>
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
                <dt class="text-xs font-semibold uppercase text-success">{{ __('Approved') }}</dt>
                <dd class="text-sm font-bold text-success" x-text="formatCurrency(summary.approved)">0</dd>
            </div>
            <div class="rounded-lg border border-warning/30 bg-warning/10 px-3 py-1.5">
                <dt class="text-xs font-semibold uppercase text-warning">{{ __('Pending') }}</dt>
                <dd class="text-sm font-bold text-warning" x-text="formatCurrency(summary.pending)">0</dd>
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
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Category') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Amount') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Date') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <template x-for="r in records" :key="r.id">
                            <tr class="transition-colors hover:bg-surface-dim">
                                <td class="px-4 py-3 text-ink" x-text="r.category?.name || '-'"></td>
                                <td class="px-4 py-3 font-medium text-ink" x-text="formatCurrency(r.amount)"></td>
                                <td class="px-4 py-3 text-ink" x-text="formatDate(r.expense_date)"></td>
                                <td class="px-4 py-3">
                                    <x-status-badge x-show="r.status === 'pending'" tone="warning" pill>{{ __('Pending') }}</x-status-badge>
                                    <x-status-badge x-show="r.status === 'approved'" tone="success" pill>{{ __('Approved') }}</x-status-badge>
                                    <x-status-badge x-show="r.status === 'rejected'" tone="error" pill>{{ __('Rejected') }}</x-status-badge>
                                    <x-status-badge x-show="r.status === 'paid'" tone="info" pill>{{ __('Paid') }}</x-status-badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button x-show="r.status === 'pending'" @click="cancelReimbursement(r.id)"
                                            class="rounded-lg px-2 py-1 text-xs font-medium text-error transition-colors hover:bg-error/5">
                                        {{ __('Cancel') }}
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <template x-if="records.length === 0">
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="material-symbols-outlined text-3xl text-on-surface-variant/30">wallet</span>
                                        <p class="text-sm font-medium text-ink">{{ __('Belum ada reimbursement') }}</p>
                                        <p class="text-xs text-on-surface-variant">{{ __('Ajukan klaim untuk mulai') }}</p>
                                        <div class="mt-2">
                                            <x-button variant="primary" href="{{ route('reimbursements.apply') }}" wire:navigate icon="add" size="sm">{{ __('New Claim') }}</x-button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div x-show="!loading" class="grid grid-cols-1 divide-y divide-outline-variant/10 lg:hidden">
                <template x-for="r in records" :key="r.id">
                    <div class="p-4">
                        <div class="mb-1 flex items-start justify-between">
                            <div>
                                <p class="text-sm font-semibold text-ink" x-text="r.category?.name || '-'"></p>
                                <div class="mt-0.5 space-x-2 text-xs text-on-surface-variant">
                                    <span x-text="formatDate(r.expense_date)"></span>
                                    <span>·</span>
                                    <span x-text="formatCurrency(r.amount)"></span>
                                </div>
                            </div>
                            <x-status-badge x-show="r.status === 'pending'" tone="warning" pill>{{ __('Pending') }}</x-status-badge>
                            <x-status-badge x-show="r.status === 'approved'" tone="success" pill>{{ __('Approved') }}</x-status-badge>
                            <x-status-badge x-show="r.status === 'rejected'" tone="error" pill>{{ __('Rejected') }}</x-status-badge>
                            <x-status-badge x-show="r.status === 'paid'" tone="info" pill>{{ __('Paid') }}</x-status-badge>
                        </div>
                        <button x-show="r.status === 'pending'" @click="cancelReimbursement(r.id)"
                                class="mt-2 rounded-lg px-2 py-1 text-xs font-medium text-error transition-colors hover:bg-error/5">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </template>
                <template x-if="records.length === 0">
                    <div class="flex flex-col items-center gap-2 py-12">
                        <span class="material-symbols-outlined text-3xl text-on-surface-variant/30">wallet</span>
                        <p class="text-sm font-medium text-ink">{{ __('Belum ada reimbursement') }}</p>
                    </div>
                </template>
            </div>
        </x-app.panel>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('reimbursementsIndex', () => ({
                records: [],
                period: '',
                loading: true,
                summary: { total: 0, approved: 0, pending: 0 },

                init() {
                    this.period = this.currentPeriod();
                    this.fetchReimbursements();
                },

                currentPeriod() {
                    const d = new Date();
                    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
                },

                lastMonthPeriod() {
                    const d = new Date();
                    d.setMonth(d.getMonth() - 1);
                    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
                },

                async fetchReimbursements() {
                    this.loading = true;
                    try {
                        const res = await fetch(`/api/v1/reimbursement?period=${this.period}&per_page=50`, {
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
                    const approved = this.records.filter(r => r.status === 'approved' || r.status === 'paid').reduce((s, r) => s + (r.amount || 0), 0);
                    const pending = this.records.filter(r => r.status === 'pending').reduce((s, r) => s + (r.amount || 0), 0);
                    this.summary = { total, approved, pending };
                },

                async cancelReimbursement(id) {
                    if (!confirm('{{ __('Batalkan pengajuan reimbursement ini?') }}')) return;
                    try {
                        const res = await fetch(`/api/v1/reimbursement/${id}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            Livewire.dispatch('toast', { variant: 'success', text: json.message || '{{ __('Dibatalkan') }}' });
                            this.fetchReimbursements();
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

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr + 'T00:00:00');
                    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                },
            }));
        });
    </script>
</x-layouts::app.sidebar>
