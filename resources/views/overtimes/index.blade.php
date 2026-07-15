<x-layouts::app.sidebar>
    <div x-data="overtimesIndex()">
        <x-page-shell title="{{ __('Lembur') }}" subtitle="{{ __('Riwayat pengajuan lembur Anda') }}">
            <x-slot:actions>
                <x-button variant="primary" href="{{ route('overtimes.apply') }}" wire:navigate icon="add">
                    {{ __('Ajukan Lembur') }}
                </x-button>
            </x-slot:actions>

        {{-- Toolbar --}}
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-48">
                <label class="mb-1.5 block text-xs font-medium text-on-surface-variant">{{ __('Period') }}</label>
                <select x-model="period" @change="fetchOvertimes()"
                        class="block w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2.5 text-sm text-ink focus:border-primary focus:ring-2 focus:ring-primary/20">
                    <option :value="currentPeriod()">{{ __('This Month') }}</option>
                    <option :value="lastMonthPeriod()">{{ __('Last Month') }}</option>
                </select>
            </div>
        </div>

        {{-- Summary stats --}}
        <dl class="mb-5 grid grid-cols-3 gap-3" x-show="!loading">
            <div class="ess-stat">
                <dt class="ess-stat__label">{{ __('Total Hours') }}</dt>
                <dd class="ess-stat__value" x-text="summary.total_hours + 'h'">0</dd>
            </div>
            <div class="ess-stat">
                <dt class="ess-stat__label text-warning/80">{{ __('Pending') }}</dt>
                <dd class="ess-stat__value text-warning" x-text="summary.pending_hours + 'h'">0</dd>
            </div>
            <div class="ess-stat">
                <dt class="ess-stat__label text-success/80">{{ __('Approved') }}</dt>
                <dd class="ess-stat__value text-success" x-text="summary.approved_hours + 'h'">0</dd>
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
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Date') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Hours') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Description') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <template x-for="o in records" :key="o.id">
                            <tr class="transition-colors hover:bg-surface-dim">
                                <td class="px-4 py-3 font-medium text-ink" x-text="formatDate(o.date)"></td>
                                <td class="px-4 py-3 text-ink" x-text="o.total_hours + 'h'"></td>
                                <td class="max-w-[240px] truncate px-4 py-3 text-ink" x-text="o.description"></td>
                                <td class="px-4 py-3">
                                    <x-status-badge x-show="o.status === 'pending'" tone="warning" pill>{{ __('Pending') }}</x-status-badge>
                                    <x-status-badge x-show="o.status === 'approved'" tone="success" pill>{{ __('Approved') }}</x-status-badge>
                                    <x-status-badge x-show="o.status === 'rejected'" tone="error" pill>{{ __('Rejected') }}</x-status-badge>
                                    <x-status-badge x-show="o.status === 'cancelled'" tone="neutral" pill>{{ __('Cancelled') }}</x-status-badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button x-show="o.status === 'pending'" @click="cancelOvertime(o.id)"
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
                                        <span class="material-symbols-outlined text-3xl text-on-surface-variant/30">bolt</span>
                                        <p class="text-sm font-medium text-ink">{{ __('Belum ada lembur') }}</p>
                                        <p class="text-xs text-on-surface-variant">{{ __('Ajukan lembur untuk mulai') }}</p>
                                        <div class="mt-2">
                                            <x-button variant="primary" href="{{ route('overtimes.apply') }}" wire:navigate icon="add" size="sm">{{ __('Request Overtime') }}</x-button>
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
                <template x-for="o in records" :key="o.id">
                    <div class="user-list-card p-4">
                        <div class="mb-1 flex items-start justify-between">
                            <div>
                                <p class="text-sm font-semibold text-ink" x-text="formatDate(o.date)"></p>
                                <div class="mt-0.5 space-x-2 text-xs text-on-surface-variant">
                                    <span x-text="o.total_hours + ' jam'"></span>
                                </div>
                            </div>
                            <x-status-badge x-show="o.status === 'pending'" tone="warning" pill>{{ __('Pending') }}</x-status-badge>
                            <x-status-badge x-show="o.status === 'approved'" tone="success" pill>{{ __('Approved') }}</x-status-badge>
                            <x-status-badge x-show="o.status === 'rejected'" tone="error" pill>{{ __('Rejected') }}</x-status-badge>
                            <x-status-badge x-show="o.status === 'cancelled'" tone="neutral" pill>{{ __('Cancelled') }}</x-status-badge>
                        </div>
                        <p class="mt-1 text-xs text-on-surface-variant" x-text="o.description"></p>
                        <button x-show="o.status === 'pending'" @click="cancelOvertime(o.id)"
                                class="mt-2 rounded-lg px-2 py-1 text-xs font-medium text-error transition-colors hover:bg-error/5">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </template>
                <template x-if="records.length === 0">
                    <div class="flex flex-col items-center gap-2 py-12">
                        <span class="material-symbols-outlined text-3xl text-on-surface-variant/30">bolt</span>
                        <p class="text-sm font-medium text-ink">{{ __('Belum ada lembur') }}</p>
                    </div>
                </template>
            </div>
        </x-app.panel>
    </div>
</x-page-shell>
</x-layouts::app.sidebar>
