<x-layouts::app.sidebar>
    <div x-data="payrollIndex()">
        {{-- Header --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-ink">{{ __('Payroll') }}</h1>
                <p class="mt-1 text-sm text-on-surface-variant">{{ __('View your payslips and salary history') }}</p>
            </div>
        </div>

        {{-- Toolbar --}}
        <x-app.panel class="mb-6">
            <div class="p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-12">
                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-on-surface-variant">{{ __('Year') }}</label>
                        <select x-model="year" @change="fetchPayrolls()" class="w-full rounded-xl border border-outline-variant bg-canvas px-4 py-2.5 text-sm text-ink">
                            <template x-for="y in years" :key="y">
                                <option :value="y" x-text="y"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>
        </x-app.panel>

        {{-- Summary stats --}}
        <dl class="mb-4 flex flex-wrap gap-2" x-show="!loading">
            <div class="rounded-xl border border-ink/20 bg-surface-container-high px-4 py-2">
                <dt class="text-xs font-semibold uppercase text-on-surface-variant">{{ __('Total Gross') }}</dt>
                <dd class="text-lg font-bold text-ink" x-text="formatCurrency(summary.total_gross)">0</dd>
            </div>
            <div class="rounded-xl border border-success/30 bg-success/10 px-4 py-2">
                <dt class="text-xs font-semibold uppercase text-success">{{ __('Take Home') }}</dt>
                <dd class="text-lg font-bold text-success" x-text="formatCurrency(summary.total_net)">0</dd>
            </div>
            <div class="rounded-xl border border-error/30 bg-error/10 px-4 py-2">
                <dt class="text-xs font-semibold uppercase text-error">{{ __('Deductions') }}</dt>
                <dd class="text-lg font-bold text-error" x-text="formatCurrency(summary.total_deduction)">0</dd>
            </div>
        </dl>

        {{-- Loading --}}
        <div x-show="loading" class="py-16 text-center">
            <span class="material-symbols-outlined animate-spin text-3xl text-on-surface-variant/40">progress_activity</span>
            <p class="mt-2 text-sm text-on-surface-variant">{{ __('Memuat...') }}</p>
        </div>

        {{-- Content Panel --}}
        <x-app.panel x-show="!loading">
            {{-- Desktop Table --}}
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-outline-variant/30 text-on-surface-variant">
                            <th class="px-4 py-3 font-semibold">{{ __('Period') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('Gross') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('Deduction') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('Net') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('Status') }}</th>
                            <th class="px-4 py-3 font-semibold text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <template x-for="p in payrolls" :key="p.id">
                            <tr class="transition-colors hover:bg-surface-container-low">
                                <td class="px-4 py-3 font-medium text-ink" x-text="p.period"></td>
                                <td class="px-4 py-3 text-ink" x-text="formatCurrency(p.gross_salary)"></td>
                                <td class="px-4 py-3 text-ink" x-text="formatCurrency(p.total_deduction)"></td>
                                <td class="px-4 py-3 font-semibold text-ink" x-text="formatCurrency(p.net_salary)"></td>
                                <td class="px-4 py-3">
                                    <x-status-badge x-show="p.status === 'published'" tone="success">{{ __('Published') }}</x-status-badge>
                                    <x-status-badge x-show="p.status === 'paid'" tone="info">{{ __('Paid') }}</x-status-badge>
                                    <x-status-badge x-show="p.status === 'draft'" tone="neutral">{{ __('Draft') }}</x-status-badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a :href="`/api/v1/payroll/${p.id}/payslip`" target="_blank"
                                       class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface-container-high transition-colors">
                                        <span class="material-symbols-outlined text-lg">download</span>
                                        PDF
                                    </a>
                                </td>
                            </tr>
                        </template>
                        <template x-if="payrolls.length === 0">
                            <tr>
                                <td colspan="6">
                                    <x-empty-state title="{{ __('Belum ada payroll') }}" description="{{ __('Payroll akan muncul setelah diproses oleh Finance') }}">
                                        <x-slot name="icon"><span class="material-symbols-outlined text-3xl text-on-surface-variant/50">payments</span></x-slot>
                                    </x-empty-state>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="divide-y divide-outline-variant/10 lg:hidden">
                <template x-for="p in payrolls" :key="p.id">
                    <article class="p-4 space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-ink" x-text="p.period"></p>
                                <p class="mt-0.5 text-xs text-on-surface-variant">
                                    <span x-text="formatCurrency(p.net_salary)"></span>
                                    <span class="text-success/70" x-text="'(take home)'"></span>
                                </p>
                            </div>
                            <x-status-badge x-show="p.status === 'published'" tone="success">{{ __('Published') }}</x-status-badge>
                            <x-status-badge x-show="p.status === 'paid'" tone="info">{{ __('Paid') }}</x-status-badge>
                            <x-status-badge x-show="p.status === 'draft'" tone="neutral">{{ __('Draft') }}</x-status-badge>
                        </div>
                        <dl class="grid grid-cols-2 gap-2 text-xs">
                            <div><dt class="text-on-surface-variant">{{ __('Gross') }}</dt><dd class="font-medium text-ink" x-text="formatCurrency(p.gross_salary)"></dd></div>
                            <div><dt class="text-on-surface-variant">{{ __('Deduction') }}</dt><dd class="font-medium text-ink" x-text="formatCurrency(p.total_deduction)"></dd></div>
                        </dl>
                        <div class="flex gap-2">
                            <a :href="`/api/v1/payroll/${p.id}/payslip`" target="_blank"
                               class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface-container-high transition-colors">
                                <span class="material-symbols-outlined text-lg">download</span>
                                PDF
                            </a>
                        </div>
                    </article>
                </template>
                <template x-if="payrolls.length === 0">
                    <x-empty-state title="{{ __('Belum ada payroll') }}" />
                </template>
            </div>
        </x-app.panel>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('payrollIndex', () => ({
                payrolls: [],
                year: new Date().getFullYear().toString(),
                years: [],
                loading: true,
                summary: { total_gross: 0, total_net: 0, total_deduction: 0 },

                init() {
                    const y = new Date().getFullYear();
                    this.years = Array.from({ length: 5 }, (_, i) => (y - 2 + i).toString());
                    this.fetchPayrolls();
                },

                async fetchPayrolls() {
                    this.loading = true;
                    try {
                        const res = await fetch(`/api/v1/payroll?year=${this.year}&per_page=50`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            this.payrolls = json.data;
                            this.calcSummary();
                        }
                    } catch { /* silent */ }
                    finally { this.loading = false; }
                },

                calcSummary() {
                    const gross = this.payrolls.reduce((s, p) => s + (p.gross_salary || 0), 0);
                    const net = this.payrolls.reduce((s, p) => s + (p.net_salary || 0), 0);
                    const ded = this.payrolls.reduce((s, p) => s + (p.total_deduction || 0), 0);
                    this.summary = { total_gross: gross, total_net: net, total_deduction: ded };
                },

                formatCurrency(val) {
                    return 'Rp ' + (val || 0).toLocaleString('id-ID');
                },
            }));
        });
    </script>
</x-layouts::app.sidebar>
