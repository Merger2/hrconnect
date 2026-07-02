export default function (role = 'employee') {
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
        role: role,

        get normalizedRole() {
            return { 'super-admin': 'hr', 'hr-manager': 'hr' }[this.role] ?? this.role;
        },

        init() {
            if (this.normalizedRole === 'employee') {
                this.tab = 'history';
            }
            if (this.normalizedRole === 'finance') {
                this.typeFilter = 'reimbursement';
            }
            this.fetchApprovals();
        },

        typeLabel(type) {
            const map = { leave: 'Cuti', overtime: 'Lembur', reimbursement: 'Klaim', Leave: 'Cuti', Overtime: 'Lembur', Reimbursement: 'Klaim' };
            return map[type] || type;
        },

        async fetchApprovals() {
            this.loading = true;
            try {
                let endpoint, params = new URLSearchParams({ per_page: '50' });
                const nr = this.normalizedRole;
                if (nr === 'employee') {
                    endpoint = '/api/v1/approvals/pending';
                    params.set('scope', 'own');
                } else if (nr === 'hr') {
                    endpoint = '/api/v1/approvals/pending';
                    params.set('all', '1');
                } else {
                    endpoint = this.tab === 'pending' ? '/api/v1/approvals/pending' : '/api/v1/approvals/history';
                }
                if (this.typeFilter && nr !== 'employee') {
                    params.set('type', this.typeFilter);
                }
                const url = endpoint + '?' + params.toString();
                const res = await fetch(url, { headers: window.apiHeaders() });
                const json = await res.json();
                if (json.status === 'success') {
                    this.approvals = json.data;
                    if (this.tab === 'pending' && nr !== 'employee') {
                        this.pendingCount = json.meta?.total || json.data.length;
                    }
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Gagal memuat approvals' });
            }
            finally { this.loading = false; }
        },

        canApprove() {
            return ['manager', 'hr', 'finance'].includes(this.normalizedRole);
        },

        async approve(id) {
            this.processing = id;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/api/v1/approvals/${id}/approve`, {
                    method: 'POST',
                    headers: { ...window.apiHeaders(), 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
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
                const res = await fetch(`/api/v1/approvals/${id}`, { headers: window.apiHeaders() });
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
                    headers: { ...window.apiHeaders(), 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
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
