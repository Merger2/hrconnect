export default function () {
    return {
        payrolls: [],
        year: new Date().getFullYear().toString(),
        years: [],
        loading: true,
        summary: { total_gross: 0, total_net: 0, total_deduction: 0 },

        init() {
            const y = new Date().getFullYear();
            this.years = Array.from({ length: 5 }, (_, i) => (y - 2 + i).toString());
            this.fetchPayrolls();
        },

        async fetchPayrolls() {
            this.loading = true;
            try {
                const res = await fetch(`/api/v1/payroll?year=${this.year}&per_page=50`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.payrolls = json.data;
                    this.calcSummary();
                }
            } catch { /* silent */ }
            finally { this.loading = false; }
        },

        calcSummary() {
            const gross = this.payrolls.reduce((s, p) => s + (p.gross_salary || 0), 0);
            const net = this.payrolls.reduce((s, p) => s + (p.net_salary || 0), 0);
            const ded = this.payrolls.reduce((s, p) => s + (p.total_deduction || 0), 0);
            this.summary = { total_gross: gross, total_net: net, total_deduction: ded };
        },

        formatCurrency(val) {
            return 'Rp ' + (val || 0).toLocaleString('id-ID');
        },
    };
}
