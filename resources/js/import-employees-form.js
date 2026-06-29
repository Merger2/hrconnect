export default function () {
    return {
        importFile: null,
        importError: '',
        importLoading: false,

        downloadTemplate() {
            const headers = ['Employee Number', 'Full Name', 'Email', 'NIK', 'Phone', 'Gender', 'Marital Status', 'Birth Date', 'Join Date', 'Department', 'Position', 'Employment Type', 'Salary Type', 'Education Level'];
            const csv = headers.join(',');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'employee_import_template.csv';
            a.click();
            URL.revokeObjectURL(url);
        },

        async submitImport() {
            if (!this.importFile) return;
            this.importError = '';
            this.importLoading = true;
            try {
                const formData = new FormData();
                formData.append('file', this.importFile);

                const res = await fetch('/api/v1/employees/import', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData,
                });

                if (!res.ok) {
                    const err = await res.json();
                    this.importError = err.message || 'Import failed';
                    return;
                }

                this.$dispatch('close-modal', 'import-employees');
                this.importFile = null;
                if (window.employeesIndexInstance) {
                    window.employeesIndexInstance.fetchEmployees();
                }
            } catch (e) {
                this.importError = 'An error occurred';
            } finally {
                this.importLoading = false;
            }
        },
    };
}
