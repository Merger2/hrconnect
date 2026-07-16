import { apiFetch } from './utils/api.js';

export default function () {
    return {
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
        companies: [],
        branches: [],
        departments: [],
        positions: [],
        managers: [],
        editing: null,

        init() {
            this.fetchLookups();
            this.$watch('editing', (val) => {
                if (val) {
                    Object.assign(this.form, {
                        name: val.email?.split('@')[0] || '',
                        email: val.email || '',
                        password: '',
                        employee_number: val.employee_number || '',
                        full_name: val.full_name || '',
                        nik: '', phone: '',
                        gender: val.gender || '',
                        marital_status: val.marital_status || '',
                        blood_type: val.blood_type || '',
                        birth_date: val.birth_date || '',
                        join_date: val.join_date || '',
                        company_id: val.company_id || val.branch?.company_id || '',
                        branch_id: val.branch?.id || '',
                        department_id: val.department?.id || '',
                        position_id: val.position?.id || '',
                        parent_id: val.manager?.id || '',
                        employment_type: val.employment_type || '',
                        salary_type: val.salary_type || '',
                        education_level: val.education_level || '',
                        institution_name: val.institution_name || '',
                        graduation_year: val.graduation_year || '',
                    });
                } else {
                    this.resetForm();
                }
            });
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
                const [companies, branches, departments, positions, managers] = await Promise.all([
                    apiFetch('/api/v1/companies?per_page=100', { credentials: 'same-origin' }),
                    apiFetch('/api/v1/branches?per_page=100', { credentials: 'same-origin' }),
                    apiFetch('/api/v1/departments?per_page=100', { credentials: 'same-origin' }),
                    apiFetch('/api/v1/positions?per_page=100', { credentials: 'same-origin' }),
                    apiFetch('/api/v1/employees?per_page=100', { credentials: 'same-origin' }),
                ]);
                this.companies = companies.data || [];
                this.branches = branches.data || [];
                this.departments = departments.data || [];
                this.positions = positions.data || [];
                this.managers = managers.data || [];
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

                const json = await apiFetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form),
                });

                if (json.status === 'success' || json.data) {
                    this.$dispatch('close-modal', 'create-employee');
                    this.resetForm();
                    this.editing = null;
                    if (window.employeesIndexInstance) {
                        window.employeesIndexInstance.fetchEmployees();
                    }
                    return;
                }

                this.formError = json.message || 'Terjadi kesalahan';
            } catch (e) {
                this.formError = e.message || 'Terjadi kesalahan';
            } finally {
                this.formLoading = false;
            }
        },
    };
}
