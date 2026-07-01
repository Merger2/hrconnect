export default function () {
    return {
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
                const res = await fetch(`/api/v1/reimbursement?period=${this.period}&per_page=50`, { headers: window.apiHeaders() });
                const json = await res.json();
                if (json.status === 'success') {
                    this.records = json.data;
                    this.calcSummary();
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Gagal memuat data klaim' });
            }
            finally { this.loading = false; }
        },

        calcSummary() {
            const total = this.records.reduce((s, r) => s + (r.amount || 0), 0);
            const approved = this.records.filter(r => r.status === 'approved' || r.status === 'paid').reduce((s, r) => s + (r.amount || 0), 0);
            const pending = this.records.filter(r => r.status === 'pending').reduce((s, r) => s + (r.amount || 0), 0);
            this.summary = { total, approved, pending };
        },

        async cancelReimbursement(id) {
            if (!confirm('Batalkan pengajuan reimbursement ini?')) return;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/api/v1/reimbursement/${id}`, {
                    method: 'DELETE',
                    headers: { ...window.apiHeaders(), 'X-CSRF-TOKEN': token },
                });
                const json = await res.json();
                if (json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Dibatalkan' });
                    this.fetchReimbursements();
                } else {
                    Livewire.dispatch('toast', { variant: 'error', text: json.message || 'Gagal' });
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Koneksi error' });
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
    };
}
