export default function () {
    return {
        approvals: [],
        tab: 'pending',
        typeFilter: '',
        loading: true,
        processing: null,
        pendingCount: 0,
        rejectModalOpen: false,
        rejectTargetId: null,
        rejectTargetName: '',
        rejectReason: '',
        detailModalOpen: false,
        detailData: null,
        detailLoading: false,

        init() {
            this.fetchApprovals();
        },

        typeLabel(type) {
            const map = { leave: 'Cuti', overtime: 'Lembur', reimbursement: 'Klaim', Leave: 'Cuti', Overtime: 'Lembur', Reimbursement: 'Klaim' };
            return map[type] || type;
        },

        async fetchApprovals() {
            this.loading = true;
            try {
                const endpoint = this.tab === 'pending' ? '/api/v1/approvals/pending' : '/api/v1/approvals/history';
                const url = endpoint + '?per_page=50' + (this.typeFilter ? `&type=${this.typeFilter}` : '');
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.approvals = json.data;
                    if (this.tab === 'pending') {
                        this.pendingCount = json.meta?.total || json.data.length;
                    }
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Gagal memuat approvals' });
            }
            finally { this.loading = false; }
        },

        async approve(id) {
            this.processing = id;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/api/v1/approvals/${id}/approve`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({}),
                });
                const json = await res.json();
                if (json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Disetujui' });
                    this.approvals = this.approvals.filter(a => a.approval_id !== id);
                    this.pendingCount = this.approvals.length;
                } else {
                    Livewire.dispatch('toast', { variant: 'error', text: json.message || 'Gagal' });
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Koneksi error' });
            }
            finally { this.processing = null; }
        },

        async openDetail(id) {
            this.detailLoading = true;
            this.detailModalOpen = true;
            this.detailData = null;
            try {
                const res = await fetch(`/api/v1/approvals/${id}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.detailData = json.data;
                } else {
                    Livewire.dispatch('toast', { variant: 'error', text: json.message || 'Gagal memuat detail' });
                    this.detailModalOpen = false;
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Koneksi error' });
                this.detailModalOpen = false;
            }
            finally { this.detailLoading = false; }
        },

        openRejectModal(id, name) {
            this.rejectTargetId = id;
            this.rejectTargetName = name || '';
            this.rejectReason = '';
            this.rejectModalOpen = true;
        },

        async confirmReject() {
            if (!this.rejectReason || this.rejectReason.length < 5) return;
            const id = this.rejectTargetId;
            this.rejectModalOpen = false;
            this.processing = id;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/api/v1/approvals/${id}/reject`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ rejection_reason: this.rejectReason }),
                });
                const json = await res.json();
                if (json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Ditolak' });
                    this.approvals = this.approvals.filter(a => a.approval_id !== id);
                    this.pendingCount = this.approvals.length;
                } else {
                    Livewire.dispatch('toast', { variant: 'error', text: json.message || 'Gagal' });
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Koneksi error' });
            }
            finally { this.processing = null; }
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },

        formatTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },

        formatCurrency(val) {
            if (val === null || val === undefined) return '-';
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val);
        },
    };
}
