export default function () {
    return {
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
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
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
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json();
                if (json.status === 'success') this.leaves = json.data;
            } catch { /* silent */ }
            finally { this.loading = false; }
        },

        async cancelLeave(id) {
            if (!confirm('Batalkan pengajuan cuti ini?')) return;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/api/v1/leave/${id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
                });
                const json = await res.json();
                if (json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Dibatalkan' });
                    this.fetchLeaves();
                    this.fetchQuota();
                } else {
                    Livewire.dispatch('toast', { variant: 'error', text: json.message || 'Gagal' });
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Koneksi error' });
            }
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        },
    };
}
