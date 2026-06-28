<x-layouts::app.sidebar>
    <div x-data="overtimesIndex()">
        {{-- Header --}}
        <div class="mb-3 flex flex-col gap-2.5 border-b border-outline-variant/50 pb-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <h1 class="truncate text-lg font-semibold tracking-tight text-ink">{{ __('Overtime') }}</h1>
                <p class="text-sm text-on-surface-variant">{{ __('View and manage your overtime records') }}</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <x-button variant="primary" href="{{ route('overtimes.apply') }}" wire:navigate icon="add">
                    {{ __('Request Overtime') }}
                </x-button>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="mb-3 rounded-xl border border-outline-variant bg-canvas p-2.5 shadow-sm">
            <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-2 xl:grid-cols-12">
                <div class="xl:col-span-2">
                    <label class="mb-1.5 block text-xs font-medium text-on-surface-variant">{{ __('Period') }}</label>
                    <select x-model="period" @change="fetchOvertimes()"
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
                <dt class="text-xs font-semibold uppercase text-on-surface-variant">{{ __('Total Hours') }}</dt>
                <dd class="text-sm font-bold text-ink" x-text="summary.total_hours + 'h'">0</dd>
            </div>
            <div class="rounded-lg border border-warning/30 bg-warning/10 px-3 py-1.5">
                <dt class="text-xs font-semibold uppercase text-warning">{{ __('Pending') }}</dt>
                <dd class="text-sm font-bold text-warning" x-text="summary.pending_hours + 'h'">0</dd>
            </div>
            <div class="rounded-lg border border-success/30 bg-success/10 px-3 py-1.5">
                <dt class="text-xs font-semibold uppercase text-success">{{ __('Approved') }}</dt>
                <dd class="text-sm font-bold text-success" x-text="summary.approved_hours + 'h'">0</dd>
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
            <div x-show="!loading" class="grid grid-cols-1 divide-y divide-outline-variant/10 lg:hidden">
                <template x-for="o in records" :key="o.id">
                    <div class="p-4">
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

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('overtimesIndex', () => ({
                records: [],
                period: '',
                loading: true,
                summary: { total_hours: 0, pending_hours: 0, approved_hours: 0 },

                init() {
                    this.period = this.currentPeriod();
                    this.fetchOvertimes();
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

                async fetchOvertimes() {
                    this.loading = true;
                    try {
                        const res = await fetch(`/api/v1/overtime?period=${this.period}&per_page=50`, {
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
                    const total = this.records.reduce((s, o) => s + (o.total_hours || 0), 0);
                    const pending = this.records.filter(o => o.status === 'pending').reduce((s, o) => s + (o.total_hours || 0), 0);
                    const approved = this.records.filter(o => o.status === 'approved').reduce((s, o) => s + (o.total_hours || 0), 0);
                    this.summary = { total_hours: total, pending_hours: pending, approved_hours: approved };
                },

                async cancelOvertime(id) {
                    if (!confirm('{{ __('Batalkan pengajuan lembur ini?') }}')) return;
                    try {
                        const res = await fetch(`/api/v1/overtime/${id}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            Livewire.dispatch('toast', { variant: 'success', text: json.message || '{{ __('Dibatalkan') }}' });
                            this.fetchOvertimes();
                        } else {
                            Livewire.dispatch('toast', { variant: 'error', text: json.message || '{{ __('Gagal') }}' });
                        }
                    } catch {
                        Livewire.dispatch('toast', { variant: 'error', text: '{{ __('Koneksi error') }}' });
                    }
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
