<x-layouts::app.sidebar :title="__('Cabang')">
<div x-data="{
    branches: [],
    loading: true,
    search: '',
    page: 1,
    lastPage: 1,
    total: 0,
    creating: false,
    editing: false,
    saving: false,
    error: '',
    selectedId: null,
    form: { name: '', address: '', latitude: '', longitude: '', radius: '' },

    init() { this.fetchBranches(); },

    async fetchBranches() {
        this.loading = true;
        try {
            const p = new URLSearchParams({ per_page: 10, page: this.page });
            if (this.search) p.set('search', this.search);
            const r = await fetch('/api/v1/branches?' + p, { headers: window.apiHeaders() });
            const j = await r.json();
            if (j.status === 'success') {
                this.branches = j.data;
                this.lastPage = j.meta.last_page;
                this.total = j.meta.total;
            }
        } catch(e) {} finally { this.loading = false; }
    },

    openCreate() { this.form = { name: '', address: '', latitude: '', longitude: '', radius: '' }; this.error = ''; this.selectedId = null; this.creating = true; },
    openEdit(b) { this.form = { name: b.name || '', address: b.address || '', latitude: b.latitude || '', longitude: b.longitude || '', radius: b.radius || '' }; this.error = ''; this.selectedId = b.id; this.editing = true; },
    closeModal() { this.creating = false; this.editing = false; },

    async save() {
        this.error = '';
        if (!this.form.name.trim()) { this.error = 'Nama cabang wajib diisi'; return; }
        this.saving = true;
        const isEdit = !!this.selectedId;
        const url = isEdit ? '/api/v1/branches/' + this.selectedId : '/api/v1/branches';
        const body = {};
        Object.keys(this.form).forEach(k => { if (this.form[k] !== '' && this.form[k] !== null) body[k] = this.form[k]; });
        try {
            const r = await fetch(url, {
                method: isEdit ? 'PUT' : 'POST',
                headers: { ...window.apiHeaders(), 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const j = await r.json();
            if (j.status === 'success') { this.closeModal(); this.fetchBranches(); this.$dispatch('toast', { variant: 'success', text: j.message }); }
            else { this.error = j.message || 'Gagal menyimpan'; }
        } catch(e) { this.$dispatch('toast', { variant: 'error', text: 'Gagal menyimpan' }); }
        finally { this.saving = false; }
    },

    async confirmDelete(b) {
        const ok = await window.HRConnectAlert.confirm('Hapus cabang ' + b.name + '?');
        if (!ok) return;
        try {
            const r = await fetch('/api/v1/branches/' + b.id, { method: 'DELETE', headers: window.apiHeaders() });
            const j = await r.json();
            if (j.status === 'success') { this.fetchBranches(); this.$dispatch('toast', { variant: 'success', text: j.message }); }
        } catch(e) { this.$dispatch('toast', { variant: 'error', text: 'Gagal menghapus' }); }
    },

    hasGeo(b) { return b.latitude && b.longitude; },
    canManage() { return {{ auth()->user()?->can('manage_branches') ? 'true' : 'false' }}; },
}">
    <x-page-shell title="{{ __('Cabang') }}" subtitle="{{ __('Kelola data cabang dan konfigurasi geofencing.') }}">
        <template x-if="canManage()">
            <x-slot:actions>
                <x-button variant="primary" icon="add" @click="openCreate()">{{ __('Tambah Cabang') }}</x-button>
            </x-slot:actions>
        </template>

        <x-slot:toolbar>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-on-surface-variant/40">
                    <span class="material-symbols-outlined text-lg">search</span>
                </span>
                <input type="search" placeholder="{{ __('Cari cabang...') }}" x-model="search" @input.debounce.300ms="page = 1; fetchBranches()"
                    class="h-10 w-full rounded-xl border border-outline-variant bg-canvas pl-10 pr-4 text-sm text-ink outline-none" />
            </div>
        </x-slot:toolbar>

        <div x-show="loading" class="py-16"><x-loading-skeleton mode="table" :rows="5" :cols="4" /></div>

        <template x-if="!loading && branches.length > 0">
            <div>
                <x-simple-table :headers="[__('Nama'), __('Alamat'), __('Geofence'), __('Aksi')]" class="hidden lg:block">
                    <template x-for="b in branches" :key="b.id">
                        <tr class="transition-colors hover:bg-surface-dim/30">
                            <td class="px-4 py-3"><span class="font-medium text-ink" x-text="b.name"></span></td>
                            <td class="px-4 py-3"><span class="text-sm text-on-surface-variant" x-text="(b.address||'-').substring(0,40)"></span></td>
                            <td class="px-4 py-3">
                                <template x-if="hasGeo(b)">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 text-xs font-medium text-success ring-1 ring-inset ring-success/20">
                                        <span class="material-symbols-outlined text-sm">location_on</span>
                                        <span x-text="parseFloat(b.latitude).toFixed(4)+', '+parseFloat(b.longitude).toFixed(4)"></span>
                                    </span>
                                    <span class="ml-2 text-xs text-on-surface-variant" x-text="b.radius?b.radius+'m':'-'"></span>
                                </template>
                                <template x-if="!hasGeo(b)"><span class="text-xs text-on-surface-variant/50">{{ __('Belum dikonfigurasi') }}</span></template>
                            </td>
                            <td class="px-4 py-3">
                                <template x-if="canManage()">
                                    <div class="flex gap-1">
                                        <x-button variant="ghost" size="sm" icon="edit" @click="openEdit(b)">{{ __('Edit') }}</x-button>
                                        <x-button variant="ghost" size="sm" icon="delete" @click="confirmDelete(b)" class="text-error">{{ __('Hapus') }}</x-button>
                                    </div>
                                </template>
                            </td>
                        </tr>
                    </template>
                </x-simple-table>
                <div class="mt-4 flex items-center justify-between rounded-lg border border-outline-variant/40 bg-surface-dim/30 px-4 py-2.5" x-show="lastPage > 1">
                    <button @click="page = Math.max(1, page - 1); fetchBranches()" :disabled="page <= 1"
                        class="flex items-center gap-1 rounded-lg border border-outline-variant/40 bg-canvas px-3 py-1.5 text-sm font-medium text-ink disabled:opacity-40">
                        <span class="material-symbols-outlined text-lg">chevron_left</span> {{ __('Sebelumnya') }}
                    </button>
                    <span class="text-sm text-on-surface-variant" x-text="'{{ __('Halaman') }} ' + page + ' / ' + lastPage + ' (' + total + ')'"></span>
                    <button @click="page = Math.min(lastPage, page + 1); fetchBranches()" :disabled="page >= lastPage"
                        class="flex items-center gap-1 rounded-lg border border-outline-variant/40 bg-canvas px-3 py-1.5 text-sm font-medium text-ink disabled:opacity-40">
                        {{ __('Selanjutnya') }} <span class="material-symbols-outlined text-lg">chevron_right</span>
                    </button>
                </div>
            </div>
        </template>

        <template x-if="!loading && branches.length === 0">
            <x-empty-state :title="__('Belum ada cabang')" :description="__('Tambahkan cabang perusahaan untuk memulai.')">
                <template x-if="canManage()">
                    <x-slot:actions><x-button variant="primary" icon="add" @click="openCreate()">{{ __('Tambah Cabang') }}</x-button></x-slot:actions>
                </template>
            </x-empty-state>
        </template>
    </x-page-shell>

    {{-- Create/Edit Modal --}}
    <template x-if="creating || editing">
        <div x-on:keydown.escape.window="closeModal()"
            class="fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto px-4 py-[calc(1rem+env(safe-area-inset-top))] sm:items-center sm:px-6" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal()" aria-hidden="true"></div>
            <div class="relative z-10 mx-auto w-full max-w-lg overflow-hidden rounded-xl bg-canvas shadow-xl" style="max-height: calc(100dvh - 2rem)" @click.stop>
                <div class="flex items-center justify-between border-b border-outline-variant/50 px-6 py-4">
                    <h2 class="text-lg font-semibold text-ink" x-text="selectedId ? '{{ __('Edit Cabang') }}' : '{{ __('Tambah Cabang') }}'"></h2>
                    <button @click="closeModal()" class="rounded-xl p-1.5 text-on-surface-variant hover:text-ink hover:bg-surface-container-high">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>
                <div x-show="error" class="mx-6 mt-4 rounded-xl bg-error/10 px-4 py-3 text-sm text-error" x-text="error"></div>
                <div class="px-6 py-4 space-y-4">
                    <div><label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Nama Cabang') }} *</label><input x-model="form.name" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink" :class="!form.name.trim() && error ? '!border-error' : ''" /></div>
                    <div><label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Alamat') }}</label><input x-model="form.address" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink" /></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Latitude') }}</label><input x-model="form.latitude" type="number" step="any" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink" /></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Longitude') }}</label><input x-model="form.longitude" type="number" step="any" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink" /></div>
                    </div>
                    <div><label class="mb-1.5 block text-sm font-medium text-ink">{{ __('Radius (meter)') }}</label><input x-model="form.radius" type="number" min="10" placeholder="100" class="w-full rounded-xl border border-outline-variant bg-canvas px-3 py-2 text-sm text-ink" /></div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-outline-variant/50 bg-surface-dim/30 px-6 py-4">
                    <x-button variant="secondary" @click="closeModal()">{{ __('Batal') }}</x-button>
                    <x-button variant="primary" @click="save()" x-bind:disabled="saving">
                        <span x-show="!saving" x-text="selectedId ? '{{ __('Perbarui') }}' : '{{ __('Simpan') }}'"></span>
                        <span x-show="saving" class="material-symbols-outlined animate-spin">progress_activity</span>
                    </x-button>
                </div>
            </div>
        </div>
    </template>
</div>
</x-layouts::app.sidebar>
