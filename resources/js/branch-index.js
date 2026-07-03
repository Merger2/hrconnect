export default function () {
    return {
        branches: [],
        loading: true,
        search: '',
        page: 1,
        lastPage: 1,
        total: 0,
        creating: false,
        editing: false,
        selectedId: null,
        form: { name: '', address: '', latitude: '', longitude: '', radius: '' },
        deleteTarget: null,

        init() {
            this.fetchBranches();
        },

        async fetchBranches() {
            this.loading = true;
            try {
                const params = new URLSearchParams({ per_page: 10, page: this.page });
                if (this.search) params.set('search', this.search);
                const res = await fetch(`/api/v1/branches?${params}`, { headers: window.apiHeaders() });
                const json = await res.json();
                if (json.status === 'success') {
                    this.branches = json.data;
                    this.lastPage = json.meta.last_page;
                    this.total = json.meta.total;
                }
            } catch {
                // silent
            } finally { this.loading = false; }
        },

        openCreate() {
            this.form = { name: '', address: '', latitude: '', longitude: '', radius: '' };
            this.selectedId = null;
            this.creating = true;
            this.$nextTick(() => this.initMap());
        },

        openEdit(branch) {
            this.form = {
                name: branch.name || '',
                address: branch.address || '',
                latitude: branch.latitude || '',
                longitude: branch.longitude || '',
                radius: branch.radius || '',
            };
            this.selectedId = branch.id;
            this.editing = true;
            this.$nextTick(() => this.initMap());
        },

        initMap() {
            // Handled by inline Alpine branchMap component
        },

        async save() {
            const method = this.selectedId ? 'PUT' : 'POST';
            const url = this.selectedId ? `/api/v1/branches/${this.selectedId}` : '/api/v1/branches';
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const body = JSON.parse(JSON.stringify(this.form));
            if (!body.latitude) delete body.latitude;
            if (!body.longitude) delete body.longitude;
            if (!body.radius) body.radius = null;

            try {
                const res = await fetch(url, {
                    method,
                    headers: { ...window.apiHeaders(), 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify(body),
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.creating = false;
                    this.editing = false;
                    this.fetchBranches();
                    this.$dispatch('toast', { variant: 'success', text: json.message });
                }
            } catch {
                this.$dispatch('toast', { variant: 'error', text: 'Gagal menyimpan' });
            }
        },

        async confirmDelete(branch) {
            const confirmed = await window.HRConnectAlert.confirm(`Hapus cabang ${branch.name}?`);
            if (!confirmed) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            try {
                const res = await fetch(`/api/v1/branches/${branch.id}`, {
                    method: 'DELETE',
                    headers: { ...window.apiHeaders(), 'X-CSRF-TOKEN': token },
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.fetchBranches();
                    this.$dispatch('toast', { variant: 'success', text: json.message });
                }
            } catch {
                this.$dispatch('toast', { variant: 'error', text: 'Gagal menghapus' });
            }
        },

        hasGeo(b) {
            return b.latitude && b.longitude;
        },
    };
}
