export default function (props = {}) {
    return {
        leaveTypes: props.leaveTypes || [],
        form: {
            leave_type_id: '',
            start_date: '',
            end_date: '',
            day_type: 'full_day',
            reason: '',
            attachment: null,
            attachment_name: '',
        },
        submitting: false,
        error: '',
        tomSelect: null,

        init() {
            this.$nextTick(() => this.initTomSelect());

            this.$watch('form.start_date', (val) => {
                if (this.$refs.endDate && val) {
                    this.$refs.endDate.min = val;
                }
                if (this.form.end_date && val && this.form.end_date < val) {
                    this.form.end_date = val;
                }
            });
        },

        initTomSelect() {
            if (typeof TomSelect === 'undefined' || !this.$refs.leaveTypeSelect) return;

            if (this.tomSelect) this.tomSelect.destroy();

            this.tomSelect = new TomSelect(this.$refs.leaveTypeSelect, {
                create: false,
                sortField: { field: 'text', direction: 'asc' },
                placeholder: 'Pilih jenis cuti...',
                onChange: (value) => { this.form.leave_type_id = value; },
            });
        },

        validate() {
            if (!this.form.leave_type_id) { this.error = 'Pilih jenis cuti'; return false; }
            if (!this.form.start_date) { this.error = 'Pilih tanggal mulai'; return false; }
            if (!this.form.end_date) { this.error = 'Pilih tanggal selesai'; return false; }
            if (this.form.start_date > this.form.end_date) { this.error = 'Tanggal selesai harus setelah atau sama dengan tanggal mulai'; return false; }
            if (this.form.reason.length < 10) { this.error = 'Alasan minimal 10 karakter'; return false; }
            this.error = '';
            return true;
        },

        async submit() {
            if (!this.validate()) return;
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

                const res = await fetch('/api/v1/leave', {
                    method: 'POST',
                    headers: { ...window.apiHeaders(), 'X-CSRF-TOKEN': token },
                    body: payload,
                });
                const json = await res.json();
                if (res.ok && json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Cuti berhasil diajukan' });
                    window.location.href = '/leaves';
                } else {
                    this.error = json.message || 'Gagal mengajukan cuti';
                }
            } catch {
                this.error = 'Koneksi error';
            } finally {
                this.submitting = false;
            }
        },
    };
}
