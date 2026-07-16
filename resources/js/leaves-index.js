import { apiFetch } from './utils/api.js';

export default function () {
    return {
        leaves: [],
        quota: [],
        loading: true,
        loadingQuota: true,

        init() {
            window.whenAuthReady().then(() => {
                if (!window.isAuthenticated) {
                    this.loading = false;
                    this.loadingQuota = false;
                    return;
                }
                this.fetchQuota();
                this.fetchLeaves();
            });
        },

        async fetchQuota() {
            await window.whenAuthReady();
            if (!window.isAuthenticated) {
                this.loadingQuota = false;
                return;
            }
            try {
                const json = await apiFetch('/api/v1/leave/quota', { credentials: 'same-origin' });
                if (json.status === 'success') this.quota = json.data;
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Gagal memuat kuota cuti' });
            }
            finally { this.loadingQuota = false; }
        },

        async fetchLeaves() {
            await window.whenAuthReady();
            if (!window.isAuthenticated) {
                this.loading = false;
                return;
            }
            this.loading = true;
            try {
                const json = await apiFetch('/api/v1/leave?per_page=50', { credentials: 'same-origin' });
                if (json.status === 'success') this.leaves = json.data;
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Gagal memuat data cuti' });
            }
            finally { this.loading = false; }
        },

        async cancelLeave(id) {
            if (!confirm('Batalkan pengajuan cuti ini?')) return;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const json = await apiFetch(`/api/v1/leave/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': token },
                });
                if (json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Dibatalkan' });
                    this.fetchLeaves();
                    this.fetchQuota();
                } else {
                    Livewire.dispatch('toast', { variant: 'error', text: json.message || 'Gagal' });
                }
            } catch (e) {
                Livewire.dispatch('toast', { variant: 'error', text: e.message || 'Koneksi error' });
            }
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        },
    };
}
