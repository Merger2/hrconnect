import { apiFetch } from './utils/api.js';

export default function () {
    return {
        form: {
            date: '',
            start_time: '',
            end_time: '',
            description: '',
        },
        submitting: false,
        error: '',

        init() {
            this.form.date = this.today();
        },

        today() {
            return new Date().toISOString().split('T')[0];
        },

        calcDuration() {
            if (!this.form.start_time || !this.form.end_time) return '';
            const [sh, sm] = this.form.start_time.split(':').map(Number);
            const [eh, em] = this.form.end_time.split(':').map(Number);
            let mins = (eh * 60 + em) - (sh * 60 + sm);
            if (mins < 0) mins += 1440;
            return (mins / 60).toFixed(1) + ' jam';
        },

        validate() {
            if (!this.form.date) { this.error = 'Pilih tanggal'; return false; }
            if (!this.form.start_time) { this.error = 'Pilih jam mulai'; return false; }
            if (!this.form.end_time) { this.error = 'Pilih jam selesai'; return false; }
            if (this.form.start_time === this.form.end_time) { this.error = 'Jam mulai dan selesai tidak boleh sama'; return false; }
            if (this.form.description.length < 10) { this.error = 'Alasan minimal 10 karakter'; return false; }
            this.error = '';
            return true;
        },

        validateStep(step) {
            if (step === 0) {
                if (!this.form.date) { this.error = 'Pilih tanggal'; return false; }
                if (!this.form.start_time) { this.error = 'Pilih jam mulai'; return false; }
                if (!this.form.end_time) { this.error = 'Pilih jam selesai'; return false; }
                if (this.form.start_time === this.form.end_time) { this.error = 'Jam mulai dan selesai tidak boleh sama'; return false; }
            }
            if (step === 1 && this.form.description.length < 10) { this.error = 'Alasan minimal 10 karakter'; return false; }
            this.error = '';
            return true;
        },

        async submit() {
            if (!this.validate()) return;
            this.submitting = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const json = await apiFetch('/api/v1/overtime', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify(this.form),
                });
                if (json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Lembur diajukan' });
                    window.location.href = '/overtimes';
                } else {
                    this.error = json.message || 'Gagal mengajukan lembur';
                }
            } catch (error) {
                this.error = error.message || 'Koneksi error';
            }
            finally { this.submitting = false; }
        },
    };
}
