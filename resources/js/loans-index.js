export default function () {
    return {
        records: [],
        statusFilter: '',
        loading: true,
        submitting: false,
        showCreateModal: false,
        formError: '',
        summary: { total: 0, active: 0, pending: 0, paidOff: 0 },
        form: { amount: '', interest_rate: 0, tenor_months: 12 },

        init() {
            this.fetchLoans();
        },

        openCreateModal() {
            this.form = { amount: '', interest_rate: 0, tenor_months: 12 };
            this.formError = '';
            this.showCreateModal = true;
        },

        async fetchLoans() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ per_page: 50 });
                if (this.statusFilter) params.set('status', this.statusFilter);
                const res = await fetch(`/api/v1/loans?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.records = json.data;
                    this.calcSummary();
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Gagal memuat data pinjaman' });
            }
            finally { this.loading = false; }
        },

        calcSummary() {
            const total = this.records.reduce((s, r) => s + (r.amount || 0), 0);
            const active = this.records.filter(r => r.status === 'active').reduce((s, r) => s + (r.amount || 0), 0);
            const pending = this.records.filter(r => r.status === 'pending').reduce((s, r) => s + (r.amount || 0), 0);
            const paidOff = this.records.filter(r => r.status === 'paid_off').reduce((s, r) => s + (r.amount || 0), 0);
            this.summary = { total, active, pending, paidOff };
        },

        async submitLoan() {
            if (!this.form.amount || this.form.amount < 1) {
                this.formError = 'Jumlah pinjaman harus diisi';
                return;
            }
            if (!this.form.tenor_months || this.form.tenor_months < 1) {
                this.formError = 'Tenor harus diisi';
                return;
            }
            this.submitting = true;
            this.formError = '';
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/api/v1/loans', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify(this.form),
                });
                const json = await res.json();
                if (json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Berhasil' });
                    this.showCreateModal = false;
                    this.fetchLoans();
                } else {
                    this.formError = json.message || 'Gagal mengajukan pinjaman';
                }
            } catch {
                this.formError = 'Koneksi error';
            }
            finally { this.submitting = false; }
        },

        async cancelLoan(id) {
            if (!confirm('Batalkan pengajuan pinjaman ini?')) return;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/api/v1/loans/${id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
                });
                const json = await res.json();
                if (json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Dibatalkan' });
                    this.fetchLoans();
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
    };
}
