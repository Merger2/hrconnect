import { apiFetch } from './utils/api.js';

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

        createModalOpen: false,
        editing: false,
        form: { name: '', email: '', password: '', employee_number: '', full_name: '', nik: '', phone: '', gender: '', marital_status: '', blood_type: '', birth_date: '', company_id: '', branch_id: '', department_id: '', position_id: '', parent_id: '', employment_type: '', salary_type: '', join_date: '', education_level: '', institution_name: '', graduation_year: '' },
        lookup: { companies: [], branches: [], departments: [], positions: [], managers: [] },

        init() {
            this.fetchDepartments();
            this.fetchEmployees();
            this.$watch('search', () => { this.page = 1; this.fetchEmployees(); });
        },

        async fetchDepartments() {
            try {
                const json = await apiFetch('/api/v1/departments?per_page=100', { credentials: 'same-origin' });
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

                const json = await apiFetch(`/api/v1/employees?${params}`, { credentials: 'same-origin' });
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
                const json = await apiFetch(`/api/v1/employees?per_page=100&${params}`, { credentials: 'same-origin' });
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
                const json = await apiFetch(`/api/v1/employees/${this.selectedEmployee.id}/terminate`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.terminateForm),
                });
                
                if (json.status === 'success') {
                    this.terminateModalOpen = false;
                    this.selectedEmployee = null;
                    this.fetchEmployees();
                } else {
                    this.terminateError = json.message || 'Gagal melakukan terminasi';
                }
            } catch (e) {
                this.terminateError = e.message || 'Terjadi kesalahan';
            } finally {
                this.terminateLoading = false;
            }
        },
    };
}
