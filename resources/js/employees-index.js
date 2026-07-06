export default function () {
    return {
        employees: [],
        departments: [],
        loading: true,
        search: '',
        view: localStorage.getItem('employeeView') || 'table',
        page: 1,
        perPage: 20,
        total: 0,
        lastPage: 1,
        filters: { status: '', department_id: '' },

        formError: '',
        formLoading: false,

        terminateModalOpen: false,
        selectedEmployee: null,
        terminateForm: { type: 'dismissed', reason: '', date: '' },
        terminateError: '',
        terminateLoading: false,

        init() {
            this.fetchDepartments();
            this.fetchEmployees();
            this.$watch('search', () => { this.page = 1; this.fetchEmployees(); });
        },

        async fetchDepartments() {
            try {
                const res = await fetch('/api/v1/departments?per_page=100', { headers: window.apiHeaders(), credentials: 'same-origin' });
                const json = await res.json();
                this.departments = json.data || [];
            } catch (e) {
                console.error('Gagal memuat departemen', e);
            }
        },

        async fetchEmployees() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.set('per_page', this.perPage);
                params.set('page', this.page);
                if (this.search.trim().length >= 2) params.set('search', this.search.trim());
                if (this.filters.status) params.set('status', this.filters.status);
                if (this.filters.department_id) params.set('department_id', this.filters.department_id);

                const res = await fetch(`/api/v1/employees?${params}`, { headers: window.apiHeaders(), credentials: 'same-origin' });
                const json = await res.json();
                this.employees = json.data || [];
                this.total = json.meta?.total || 0;
                this.lastPage = json.meta?.last_page || 1;
            } catch (e) {
                console.error('Gagal memuat karyawan', e);
                this.employees = [];
                this.total = 0;
            } finally {
                this.loading = false;
            }
        },

        toggleView() {
            this.view = this.view === 'table' ? 'grid' : 'table';
            localStorage.setItem('employeeView', this.view);
        },

        statusLabel(status) {
            const labels = { active: 'Aktif', inactive: 'Tidak Aktif', resigned: 'Resign', terminated: 'PHK', deceased: 'Meninggal' };
            return labels[status] || status;
        },

        statusClass(status) {
            const classes = {
                active: 'bg-success/10 text-success ring-success/20',
                inactive: 'bg-surface-dim text-on-surface-variant ring-outline-variant/30',
                resigned: 'bg-warning/10 text-warning ring-warning/20',
                terminated: 'bg-error/10 text-error ring-error/20',
                deceased: 'bg-error/10 text-error ring-error/20',
            };
            return classes[status] || 'bg-surface-dim text-on-surface-variant ring-outline-variant/30';
        },

        async exportCSV() {
            try {
                const params = new URLSearchParams();
                if (this.filters.status) params.set('status', this.filters.status);
                if (this.filters.department_id) params.set('department_id', this.filters.department_id);
                const res = await fetch(`/api/v1/employees?per_page=100&${params}`, { headers: window.apiHeaders(), credentials: 'same-origin' });
                const json = await res.json();
                const data = json.data || [];
                if (data.length === 0) return;
                const headers = ['No. Karyawan', 'Nama Lengkap', 'Departemen', 'Jabatan', 'Status', 'Tanggal Masuk'];
                const rows = data.map(e => [
                    e.employee_number, e.full_name,
                    e.department?.name || '', e.position?.name || '',
                    e.status, e.join_date || ''
                ]);
                const csv = [headers.join(','), ...rows.map(r => r.map(v => `"${v}"`).join(','))].join('\n');
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `employees_${new Date().toISOString().slice(0, 10)}.csv`;
                a.click();
                URL.revokeObjectURL(url);
            } catch (e) {
                console.error('Gagal mengexport', e);
            }
        },

        openTerminateModal(employee) {
            this.selectedEmployee = employee;
            this.terminateForm = {
                type: 'dismissed',
                reason: '',
                date: new Date().toISOString().slice(0, 10),
            };
            this.terminateError = '';
            this.terminateModalOpen = true;
        },

        async submitTerminate() {
            if (!this.selectedEmployee) return;
            this.terminateError = '';
            this.terminateLoading = true;
            try {
                const res = await fetch(`/api/v1/employees/${this.selectedEmployee.id}/terminate`, {
                    method: 'POST',
                    headers: { ...window.apiHeaders(), 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        type: this.terminateForm.type,
                        reason: this.terminateForm.reason,
                        date: this.terminateForm.date,
                    }),
                });

                if (!res.ok) {
                    const err = await res.json();
                    this.terminateError = err.message || 'Gagal melakukan PHK';
                    return;
                }

                this.terminateModalOpen = false;
                this.selectedEmployee = null;
                this.fetchEmployees();
            } catch (e) {
                this.terminateError = 'Terjadi kesalahan';
            } finally {
                this.terminateLoading = false;
            }
        },
    };
}
