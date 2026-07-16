import { apiFetch } from './utils/api.js';

export default function () {
    return {
        records: [],
        statusFilter: '',
        categoryFilter: '',
        search: '',
        loading: true,
        submitting: false,
        showCreateModal: false,
        showHandoverModal: false,
        formError: '',
        handoverError: '',
        handoverAsset: null,
        total: 0,
        available: 0,
        assigned: 0,
        disposed: 0,
        form: { name: '', serial_number: '', code: '', category: '' },
        handoverForm: { employee_id: '', handover_date: '', condition: 'baik' },

        init() {
            this.fetchAssets();
        },

        openCreateModal() {
            this.form = { name: '', serial_number: '', code: '', category: '' };
            this.formError = '';
            this.showCreateModal = true;
        },

        openHandoverModal(asset) {
            this.handoverAsset = asset;
            this.handoverForm = { employee_id: '', handover_date: new Date().toISOString().split('T')[0], condition: 'baik' };
            this.handoverError = '';
            this.showHandoverModal = true;
        },

        async fetchAssets() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ per_page: 50 });
                if (this.statusFilter) params.set('status', this.statusFilter);
                if (this.categoryFilter) params.set('category', this.categoryFilter);
                if (this.search) params.set('search', this.search);
                const json = await apiFetch(`/api/v1/assets?${params}`, { credentials: 'same-origin' });
                if (json.status === 'success') {
                    this.records = json.data;
                    this.calcSummary();
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Gagal memuat data aset' });
            }
            finally { this.loading = false; }
        },

        calcSummary() {
            this.total = this.records.length;
            this.available = this.records.filter(r => r.status === 'available').length;
            this.assigned = this.records.filter(r => r.status === 'assigned').length;
            this.disposed = this.records.filter(r => r.status === 'disposed').length;
        },

        async submitAsset() {
            if (!this.form.name || !this.form.serial_number) {
                this.formError = 'Nama dan nomor seri harus diisi';
                return;
            }
            this.submitting = true;
            this.formError = '';
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const json = await apiFetch('/api/v1/assets', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify(this.form),
                });
                if (json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Berhasil' });
                    this.showCreateModal = false;
                    this.fetchAssets();
                } else {
                    this.formError = json.message || 'Gagal menambahkan aset';
                }
            } catch {
                this.formError = 'Koneksi error';
            }
            finally { this.submitting = false; }
        },

        async submitHandover() {
            if (!this.handoverForm.employee_id || !this.handoverForm.handover_date) {
                this.handoverError = 'Data harus diisi lengkap';
                return;
            }
            this.submitting = true;
            this.handoverError = '';
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const json = await apiFetch(`/api/v1/assets/${this.handoverAsset.id}/handover`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify(this.handoverForm),
                });
                if (json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Berhasil' });
                    this.showHandoverModal = false;
                    this.fetchAssets();
                } else {
                    this.handoverError = json.message || 'Gagal';
                }
            } catch {
                this.handoverError = 'Koneksi error';
            }
            finally { this.submitting = false; }
        },

        async deleteAsset(id) {
            if (!confirm('Hapus aset ini?')) return;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const json = await apiFetch(`/api/v1/assets/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': token },
                });
                if (json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Berhasil' });
                    this.fetchAssets();
                } else {
                    Livewire.dispatch('toast', { variant: 'error', text: json.message || 'Gagal' });
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Koneksi error' });
            }
        },
    };
}
