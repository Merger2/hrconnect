<x-layouts::app.sidebar>
    <div x-data="reimbursementsIndex()">
        <x-page-shell title="{{ __('Klaim') }}" subtitle="{{ __('Riwayat pengajuan klaim Anda') }}">
            <x-slot:actions>
                <x-button variant="primary" href="{{ route('reimbursements.apply') }}" wire:navigate icon="add">
                    {{ __('Ajukan Klaim') }}
                </x-button>
            </x-slot:actions>

        {{-- Toolbar --}}
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-48">
                <label class="mb-1.5 block text-xs font-medium text-on-surface-variant">{{ __('Period') }}</label>
                <select x-model="period" @change="fetchReimbursements()"
                        class="block w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2.5 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <option :value="currentPeriod()">{{ __('This Month') }}</option>
                    <option :value="lastMonthPeriod()">{{ __('Last Month') }}</option>
                </select>
            </div>
        </div>

        {{-- Summary stats --}}
        <dl class="mb-5 grid grid-cols-3 gap-3" x-show="!loading">
            <div class="ess-stat">
                <dt class="ess-stat__label">{{ __('Total') }}</dt>
                <dd class="text-base font-bold text-ink tabular-nums" x-text="formatCurrency(summary.total)">0</dd>
            </div>
            <div class="ess-stat">
                <dt class="ess-stat__label text-success/80">{{ __('Approved') }}</dt>
                <dd class="text-base font-bold text-success tabular-nums" x-text="formatCurrency(summary.approved)">0</dd>
            </div>
            <div class="ess-stat">
                <dt class="ess-stat__label text-warning/80">{{ __('Pending') }}</dt>
                <dd class="text-base font-bold text-warning tabular-nums" x-text="formatCurrency(summary.pending)">0</dd>
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
            <div x-show="!loading" class="space-y-3 lg:hidden">
                <template x-for="r in records" :key="r.id">
                    <div class="user-list-card p-4">
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
</x-page-shell>
</x-layouts::app.sidebar>
