import { apiFetch } from './utils/api.js';

export default function () {
    return {
        importFile: null,
        importError: '',
        importLoading: false,

        downloadTemplate() {
            const headers = ['No. Karyawan', 'Nama Lengkap', 'Email', 'NIK', 'Telepon', 'Jenis Kelamin', 'Status Perkawinan', 'Tanggal Lahir', 'Tanggal Masuk', 'Departemen', 'Jabatan', 'Jenis Pegawai', 'Jenis Gaji', 'Pendidikan'];
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

                const json = await apiFetch('/api/v1/employees/import', {
                    method: 'POST',
                    body: formData,
                });

                if (json.status === 'success' || json.data) {
                    this.$dispatch('close-modal', 'import-employees');
                    this.importFile = null;
                    if (window.employeesIndexInstance) {
                        window.employeesIndexInstance.fetchEmployees();
                    }
                    return;
                }

                this.importError = json.message || 'Gagal import';
            } catch {
                this.importError = 'Terjadi kesalahan';
            } finally {
                this.importLoading = false;
            }
        },
    };
}
