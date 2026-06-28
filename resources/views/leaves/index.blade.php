<x-layouts::app.sidebar>
    <div x-data="leavesIndex()">
        {{-- Header --}}
        <div class="mb-3 flex flex-col gap-2.5 border-b border-outline-variant/50 pb-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <h1 class="truncate text-lg font-semibold tracking-tight text-ink">{{ __('Leave Requests') }}</h1>
                <p class="text-sm text-on-surface-variant">{{ __('View and manage your leave applications') }}</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <x-button variant="primary" href="{{ route('leaves.apply') }}" wire:navigate icon="add">
                    {{ __('Apply Leave') }}
                </x-button>
            </div>
        </div>

        {{-- Quota --}}
        <div x-show="!loadingQuota" class="mb-4 flex flex-wrap gap-2">
            <template x-for="q in quota" :key="q.leave_type?.id || q.leave_type?.code">
                <div class="rounded-lg border border-outline-variant bg-surface-container-low px-3 py-1.5 text-center">
                    <p class="text-xs font-medium text-on-surface-variant" x-text="q.leave_type?.name || 'Leave'"></p>
                    <p class="text-sm font-bold text-ink" x-text="`${q.used} / ${q.quota}`"></p>
                </div>
            </template>
        </div>

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
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Type') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('From') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('To') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Days') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <template x-for="l in leaves" :key="l.id">
                            <tr class="transition-colors hover:bg-surface-dim">
                                <td class="px-4 py-3 text-ink" x-text="l.leave_type?.name || l.leave_type?.code || '-'"></td>
                                <td class="px-4 py-3 text-ink" x-text="formatDate(l.start_date)"></td>
                                <td class="px-4 py-3 text-ink" x-text="formatDate(l.end_date)"></td>
                                <td class="px-4 py-3 text-ink" x-text="l.total_days"></td>
                                <td class="px-4 py-3">
                                    <x-status-badge x-show="l.status === 'pending'" tone="warning" pill>{{ __('Pending') }}</x-status-badge>
                                    <x-status-badge x-show="l.status === 'approved'" tone="success" pill>{{ __('Approved') }}</x-status-badge>
                                    <x-status-badge x-show="l.status === 'rejected'" tone="error" pill>{{ __('Rejected') }}</x-status-badge>
                                    <x-status-badge x-show="l.status === 'cancelled'" tone="neutral" pill>{{ __('Cancelled') }}</x-status-badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button x-show="l.status === 'pending'" @click="cancelLeave(l.id)"
                                            class="rounded-lg px-2 py-1 text-xs font-medium text-error transition-colors hover:bg-error/5">
                                        {{ __('Cancel') }}
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <template x-if="leaves.length === 0">
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="material-symbols-outlined text-3xl text-on-surface-variant/30">calendar_month</span>
                                        <p class="text-sm font-medium text-ink">{{ __('Belum ada pengajuan cuti') }}</p>
                                        <p class="text-xs text-on-surface-variant">{{ __('Ajukan cuti untuk mulai') }}</p>
                                        <div class="mt-2">
                                            <x-button variant="primary" href="{{ route('leaves.apply') }}" wire:navigate icon="add" size="sm">{{ __('Apply Leave') }}</x-button>
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
                <template x-for="l in leaves" :key="l.id">
                    <div class="p-4">
                        <div class="mb-1 flex items-start justify-between">
                            <div>
                                <p class="text-sm font-semibold text-ink" x-text="l.leave_type?.name || l.leave_type?.code || '-'"></p>
                                <div class="mt-0.5 space-x-2 text-xs text-on-surface-variant">
                                    <span x-text="formatDate(l.start_date)"></span>
                                    <span x-show="l.start_date !== l.end_date">— <span x-text="formatDate(l.end_date)"></span></span>
                                    <span>·</span>
                                    <span x-text="l.total_days + ' {{ __('hari') }}'"></span>
                                </div>
                            </div>
                            <x-status-badge x-show="l.status === 'pending'" tone="warning" pill>{{ __('Pending') }}</x-status-badge>
                            <x-status-badge x-show="l.status === 'approved'" tone="success" pill>{{ __('Approved') }}</x-status-badge>
                            <x-status-badge x-show="l.status === 'rejected'" tone="error" pill>{{ __('Rejected') }}</x-status-badge>
                            <x-status-badge x-show="l.status === 'cancelled'" tone="neutral" pill>{{ __('Cancelled') }}</x-status-badge>
                        </div>
                        <button x-show="l.status === 'pending'" @click="cancelLeave(l.id)"
                                class="mt-2 rounded-lg px-2 py-1 text-xs font-medium text-error transition-colors hover:bg-error/5">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </template>
                <template x-if="leaves.length === 0">
                    <div class="flex flex-col items-center gap-2 py-12">
                        <span class="material-symbols-outlined text-3xl text-on-surface-variant/30">calendar_month</span>
                        <p class="text-sm font-medium text-ink">{{ __('Belum ada pengajuan cuti') }}</p>
                    </div>
                </template>
            </div>
        </x-app.panel>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('leavesIndex', () => ({
                leaves: [],
                quota: [],
                loading: true,
                loadingQuota: true,

                init() {
                    this.fetchQuota();
                    this.fetchLeaves();
                },

                async fetchQuota() {
                    try {
                        const res = await fetch('/api/v1/leave/quota', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        });
                        const json = await res.json();
                        if (json.status === 'success') this.quota = json.data;
                    } catch { /* silent */ }
                    finally { this.loadingQuota = false; }
                },

                async fetchLeaves() {
                    this.loading = true;
                    try {
                        const res = await fetch('/api/v1/leave?per_page=50', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        });
                        const json = await res.json();
                        if (json.status === 'success') this.leaves = json.data;
                    } catch { /* silent */ }
                    finally { this.loading = false; }
                },

                async cancelLeave(id) {
                    if (!confirm('{{ __('Batalkan pengajuan cuti ini?') }}')) return;
                    try {
                        const res = await fetch(`/api/v1/leave/${id}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            Livewire.dispatch('toast', { variant: 'success', text: json.message || '{{ __('Dibatalkan') }}' });
                            this.fetchLeaves();
                            this.fetchQuota();
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
