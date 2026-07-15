<x-layouts::app.sidebar>
    <div x-data="payrollIndex()">
        <x-page-shell title="{{ __('Payroll') }}" subtitle="{{ __('View your payslips and salary history') }}">

        {{-- Toolbar --}}
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-40">
                <label class="mb-1.5 block text-xs font-medium text-on-surface-variant">{{ __('Year') }}</label>
                <select x-model="year" @change="fetchPayrolls()"
                        class="block w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2.5 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <template x-for="y in years" :key="y">
                        <option :value="y" x-text="y"></option>
                    </template>
                </select>
            </div>
        </div>

        {{-- Summary stats --}}
        <dl class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3" x-show="!loading">
            <div class="ess-stat">
                <dt class="ess-stat__label">{{ __('Total Gross') }}</dt>
                <dd class="text-lg font-bold text-ink tabular-nums" x-text="formatCurrency(summary.total_gross)">0</dd>
            </div>
            <div class="ess-stat">
                <dt class="ess-stat__label text-success/80">{{ __('Take Home') }}</dt>
                <dd class="text-lg font-bold text-success tabular-nums" x-text="formatCurrency(summary.total_net)">0</dd>
            </div>
            <div class="ess-stat">
                <dt class="ess-stat__label text-error/80">{{ __('Deductions') }}</dt>
                <dd class="text-lg font-bold text-error tabular-nums" x-text="formatCurrency(summary.total_deduction)">0</dd>
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
            <div class="space-y-3 lg:hidden">
                <template x-for="p in payrolls" :key="p.id">
                    <article class="user-list-card p-4 space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-ink" x-text="p.period"></p>
                                <p class="mt-0.5 text-xs text-on-surface-variant">
                                    <span x-text="formatCurrency(p.net_salary)"></span>
                                    <span class="text-success/70">{{ __('(take home)') }}</span>
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
        </x-page-shell>
    </div>
</x-layouts::app.sidebar>
