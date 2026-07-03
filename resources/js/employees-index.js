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

        createModalOpen: false,
        editing: null,
        form: {
            name: '', email: '', password: '',
            employee_number: '', full_name: '', nik: '', phone: '',
            gender: '', marital_status: '', blood_type: '',
            birth_date: '', join_date: '',
            company_id: '', branch_id: '', department_id: '', position_id: '',
            parent_id: '', employment_type: '', salary_type: '',
            education_level: '', institution_name: '', graduation_year: '',
        },
        formError: '',
        formLoading: false,
        lookup: { companies: [], branches: [], departments: [], positions: [], managers: [] },

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
                const res = await fetch('/api/v1/departments?per_page=200', { headers: window.apiHeaders(), credentials: 'same-origin' });
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
                const res = await fetch(`/api/v1/employees?per_page=1000&${params}`, { headers: window.apiHeaders(), credentials: 'same-origin' });
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

        openCreateModal() {
            this.editing = null;
            this.resetForm();
            this.fetchLookups();
            this.createModalOpen = true;
        },

        openEditModal(employee) {
            this.editing = employee;
            this.fetchLookups();
            Object.assign(this.form, {
                name: employee.email?.split('@')[0] || '',
                email: employee.email || '',
                password: '',
                employee_number: employee.employee_number || '',
                full_name: employee.full_name || '',
                nik: '', phone: '',
                gender: employee.gender || '',
                marital_status: employee.marital_status || '',
                blood_type: employee.blood_type || '',
                birth_date: employee.birth_date || '',
                join_date: employee.join_date || '',
                company_id: employee.company_id || '',
                branch_id: employee.branch?.id || '',
                department_id: employee.department?.id || '',
                position_id: employee.position?.id || '',
                parent_id: employee.manager?.id || '',
                employment_type: employee.employment_type || '',
                salary_type: employee.salary_type || '',
                education_level: employee.education_level || '',
                institution_name: employee.institution_name || '',
                graduation_year: employee.graduation_year || '',
            });
            this.createModalOpen = true;
        },

        resetForm() {
            this.form = {
                name: '', email: '', password: '',
                employee_number: '', full_name: '', nik: '', phone: '',
                gender: '', marital_status: '', blood_type: '',
                birth_date: '', join_date: '',
                company_id: '', branch_id: '', department_id: '', position_id: '',
                parent_id: '', employment_type: '', salary_type: '',
                education_level: '', institution_name: '', graduation_year: '',
            };
            this.formError = '';
        },

        async fetchLookups() {
            try {
                const [cRes, bRes, dRes, pRes, mRes] = await Promise.all([
                    fetch('/api/v1/companies?per_page=200', { headers: window.apiHeaders(), credentials: 'same-origin' }),
                    fetch('/api/v1/branches?per_page=200', { headers: window.apiHeaders(), credentials: 'same-origin' }),
                    fetch('/api/v1/departments?per_page=200', { headers: window.apiHeaders(), credentials: 'same-origin' }),
                    fetch('/api/v1/positions?per_page=200', { headers: window.apiHeaders(), credentials: 'same-origin' }),
                    fetch('/api/v1/employees?per_page=200', { headers: window.apiHeaders(), credentials: 'same-origin' }),
                ]);
                this.lookup.companies = (await cRes.json()).data || [];
                this.lookup.branches = (await bRes.json()).data || [];
                this.lookup.departments = (await dRes.json()).data || [];
                this.lookup.positions = (await pRes.json()).data || [];
                this.lookup.managers = (await mRes.json()).data || [];
            } catch (e) {
                console.error('Gagal memuat data referensi', e);
            }
        },

        async submitEmployee() {
            this.formError = '';
            this.formLoading = true;
            try {
                const isEdit = this.editing?.id;
                const url = isEdit ? `/api/v1/employees/${this.editing.id}` : '/api/v1/employees';
                const method = isEdit ? 'PUT' : 'POST';

                const res = await fetch(url, {
                    method,
                    headers: { ...window.apiHeaders(), 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form),
                });

                if (!res.ok) {
                    const err = await res.json();
                    this.formError = err.message || Object.values(err.errors || {}).flat().join(', ');
                    return;
                }

                this.createModalOpen = false;
                this.editing = null;
                this.fetchEmployees();
            } catch (e) {
                this.formError = 'Terjadi kesalahan';
            } finally {
                this.formLoading = false;
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
