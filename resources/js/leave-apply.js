import { apiFetch } from './utils/api.js';

export default function wizardLeaveApply(props = {}) {
    const base = window.leaveApply(props);
    return {
        ...base,

        init() {
            base.init?.call(this);
            this.$nextTick(() => this.initWizard());
        },

        validateStep(step) {
            if (step === 0) {
                if (!this.form.leave_type_id) { this.error = 'Pilih jenis cuti'; return false; }
                if (!this.form.start_date) { this.error = 'Pilih tanggal mulai'; return false; }
                if (!this.form.end_date) { this.error = 'Pilih tanggal selesai'; return false; }
                if (this.form.start_date > this.form.end_date) { this.error = 'Tanggal selesai harus setelah atau sama dengan tanggal mulai'; return false; }
            }
            if (step === 1 && this.form.reason.length < 10) { this.error = 'Alasan minimal 10 karakter'; return false; }
            this.error = '';
            return true;
        },

        async submit() {
            if (!this.validateStep(this.currentStep) || this.submitting) return;
            this.submitting = true;

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const payload = new FormData();
                payload.append('leave_type_id', this.form.leave_type_id);
                payload.append('start_date', this.form.start_date);
                payload.append('end_date', this.form.end_date);
                payload.append('day_type', this.form.day_type);
                payload.append('reason', this.form.reason);
                if (this.form.attachment) {
                    payload.append('attachment', this.form.attachment);
                }

                const json = await apiFetch('/api/v1/leave', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token },
                    body: payload,
                });
                
                if (json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Cuti berhasil diajukan' });
                    window.location.href = '/leaves';
                } else {
                    this.error = json.message || 'Gagal mengajukan cuti';
                }
            } catch (error) {
                this.error = error.message || 'Koneksi error';
            } finally {
                this.submitting = false;
            }
        },
    };
}

window.wizardLeaveApply = wizardLeaveApply;
