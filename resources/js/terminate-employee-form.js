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
                const res = await fetch(`/api/v1/employees/${this.selectedEmployee.id}`, {
                    method: 'PUT',
                    headers: { ...window.apiHeaders(), 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        status: 'terminated',
                        termination_type: this.terminateForm.type,
                        termination_reason: this.terminateForm.reason,
                        resign_date: this.terminateForm.date,
                    }),
                });

                if (!res.ok) {
                    const err = await res.json();
                    this.terminateError = err.message || 'Gagal melakukan PHK';
                    return;
                }

                this.$dispatch('close-modal', 'terminate-employee');
                this.selectedEmployee = null;
                if (window.employeesIndexInstance) {
                    window.employeesIndexInstance.fetchEmployees();
                }
            } catch (e) {
                this.terminateError = 'Terjadi kesalahan';
            } finally {
                this.terminateLoading = false;
            }
        },
    };
}
