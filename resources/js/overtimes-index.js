export default function () {
    return {
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
                const res = await fetch(`/api/v1/overtime?period=${this.period}&per_page=50`, { headers: window.apiHeaders() });
                const json = await res.json();
                if (json.status === 'success') {
                    this.records = json.data;
                    this.calcSummary();
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Gagal memuat data lembur' });
            }
            finally { this.loading = false; }
        },

        calcSummary() {
            const total = this.records.reduce((s, o) => s + (o.total_hours || 0), 0);
            const pending = this.records.filter(o => o.status === 'pending').reduce((s, o) => s + (o.total_hours || 0), 0);
            const approved = this.records.filter(o => o.status === 'approved').reduce((s, o) => s + (o.total_hours || 0), 0);
            this.summary = { total_hours: total, pending_hours: pending, approved_hours: approved };
        },

        async cancelOvertime(id) {
            if (!confirm('Batalkan pengajuan lembur ini?')) return;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/api/v1/overtime/${id}`, {
                    method: 'DELETE',
                    headers: { ...window.apiHeaders(), 'X-CSRF-TOKEN': token },
                });
                const json = await res.json();
                if (json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Dibatalkan' });
                    this.fetchOvertimes();
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
