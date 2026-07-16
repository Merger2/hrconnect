import { apiFetch } from './utils/api.js';

export default function () {
    return {
        terminateForm: {
            type: 'phk',
            reason: '',
            date: new Date().toISOString().slice(0, 10),
        },
        terminateError: '',
        terminateLoading: false,
        selectedEmployee: null,

        init() {
            this.$watch('selectedEmployee', (val) => {
                if (val) {
                    this.terminateForm.date = new Date().toISOString().slice(0, 10);
                    this.terminateForm.reason = '';
                    this.terminateForm.type = 'phk';
                    this.terminateError = '';
                }
            });
        },

        async submitTerminate() {
            if (!this.selectedEmployee) return;
            this.terminateError = '';
            this.terminateLoading = true;
            try {
                const json = await apiFetch(`/api/v1/employees/${this.selectedEmployee.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        status: 'terminated',
                        termination_type: this.terminateForm.type,
                        termination_reason: this.terminateForm.reason,
                        resign_date: this.terminateForm.date,
                    }),
                });

                if (json.status === 'success') {
                    this.$dispatch('close-modal', 'terminate-employee');
                    this.selectedEmployee = null;
                    if (window.employeesIndexInstance) {
                        window.employeesIndexInstance.fetchEmployees();
                    }
                    return;
                }

                this.terminateError = json.message || 'Gagal melakukan PHK';
            } catch (e) {
                this.terminateError = e.message || 'Terjadi kesalahan';
            } finally {
                this.terminateLoading = false;
            }
        },
    };
}
