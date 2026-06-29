export default function () {
    return {
        leaveTypes: [],
        form: {
            leave_type_id: '',
            start_date: '',
            end_date: '',
            day_type: 'full_day',
            reason: '',
        },
        submitting: false,
        error: '',

        init() {
            this.fetchLeaveTypes();
        },

        async fetchLeaveTypes() {
            try {
                const res = await fetch('/api/v1/leave/quota', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.leaveTypes = json.data.map(q => q.leave_type).filter(Boolean);
                }
            } catch { /* silent */ }
        },

        validate() {
            if (!this.form.leave_type_id) { this.error = 'Pilih tipe cuti'; return false; }
            if (!this.form.start_date) { this.error = 'Pilih tanggal mulai'; return false; }
            if (!this.form.end_date) { this.error = 'Pilih tanggal selesai'; return false; }
            if (this.form.start_date > this.form.end_date) { this.error = 'Tanggal selesai harus setelah tanggal mulai'; return false; }
            if (this.form.reason.length < 10) { this.error = 'Alasan minimal 10 karakter'; return false; }
            this.error = '';
            return true;
        },

        async submit() {
            if (!this.validate()) return;
            this.submitting = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/api/v1/leave', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify(this.form),
                });
                const json = await res.json();
                if (res.ok && json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Cuti diajukan' });
                    window.location.href = '/leaves';
                } else {
                    this.error = json.message || 'Gagal mengajukan cuti';
                }
            } catch {
                this.error = 'Koneksi error';
            }
            finally { this.submitting = false; }
        },
    };
}
