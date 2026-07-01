export default function () {
    return {
        categories: [],
        form: {
            category_id: '',
            title: '',
            amount: '',
            expense_date: '',
            description: '',
            receipt: null,
            receipt_name: '',
        },
        submitting: false,
        error: '',

        init() {
            this.fetchCategories();
            this.form.expense_date = this.today();
        },

        today() {
            return new Date().toISOString().split('T')[0];
        },

        async fetchCategories() {
            try {
                const res = await fetch('/api/v1/reimbursement/categories', { headers: window.apiHeaders() });
                const json = await res.json();
                if (json.status === 'success') this.categories = json.data;
            } catch {
                this.error = 'Gagal memuat kategori';
            }
        },

        handleFile(event) {
            const file = event.target.files[0];
            if (file) {
                this.form.receipt = file;
                this.form.receipt_name = file.name;
            }
        },

        validate() {
            if (!this.form.category_id) { this.error = 'Pilih kategori'; return false; }
            if (!this.form.amount || this.form.amount < 1) { this.error = 'Masukkan jumlah yang valid'; return false; }
            if (!this.form.expense_date) { this.error = 'Pilih tanggal'; return false; }
            if ((this.form.description || '').length < 10) { this.error = 'Deskripsi minimal 10 karakter'; return false; }
            if (!this.form.receipt) { this.error = 'Upload bukti pembayaran'; return false; }
            this.error = '';
            return true;
        },

        async submit() {
            if (!this.validate()) return;
            this.submitting = true;
            try {
                const fd = new FormData();
                fd.append('category_id', this.form.category_id);
                fd.append('amount', this.form.amount);
                fd.append('expense_date', this.form.expense_date);
                fd.append('description', this.form.description);
                if (this.form.title) fd.append('title', this.form.title);
                if (this.form.receipt) fd.append('receipt', this.form.receipt);
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                const res = await fetch('/api/v1/reimbursement', {
                    method: 'POST',
                    headers: { ...window.apiHeaders(), 'X-CSRF-TOKEN': token },
                    body: fd,
                });
                const json = await res.json();
                if (res.ok && json.status === 'success') {
                    Livewire.dispatch('toast', { variant: 'success', text: json.message || 'Klaim diajukan' });
                    window.location.href = '/reimbursements';
                } else {
                    this.error = json.message || 'Gagal mengajukan klaim';
                }
            } catch {
                this.error = 'Koneksi error';
            }
            finally { this.submitting = false; }
        },
    };
}
