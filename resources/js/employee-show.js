export default function () {
    return {
        employee: {},
        pii: {},
        families: [],
        loading: false,
        hasPiiAccess: false,

        init() {
            if (this.hasPiiAccess) {
                this.fetchPii();
            }
        },

        get hasAddress() {
            return !!(this.employee.address || this.employee.province || this.employee.city
                || this.employee.district || this.employee.village);
        },

        async fetchPii() {
            try {
                const json = await apiFetch(`/api/v1/employees/${this.employee.id}/pii`, { credentials: 'same-origin' });
                this.pii = json.data || {};
            } catch {
                this.pii = {};
            }
        },

        statusLabel(status) {
            const labels = { active: 'Aktif', inactive: 'Nonaktif', resigned: 'Resign', terminated: 'PHK', deceased: 'Meninggal' };
            return labels[status] || status;
        },

        genderLabel(g) {
            return g === 'L' ? 'Laki-laki' : g === 'P' ? 'Perempuan' : '-';
        },

        statusClass(status) {
            const map = { active: 'bg-success/10 text-success ring-success/20', inactive: 'bg-surface-dim text-on-surface-variant ring-outline-variant/30', resigned: 'bg-warning/10 text-warning ring-warning/20', terminated: 'bg-error/10 text-error ring-error/20', deceased: 'bg-surface-dim text-on-surface-variant ring-outline-variant/30' };
            return 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ' + (map[status] || '');
        },

        maritalLabel(m) {
            const labels = { single: 'Lajang', married: 'Menikah', divorced: 'Cerai', widowed: 'Duda/Janda' };
            return labels[m] || m || '-';
        },

        employmentLabel(e) {
            const labels = { permanent: 'Tetap', contract: 'Kontrak', probation: 'Percobaan', intern: 'Magang' };
            return labels[e] || e || '-';
        },
    };
}
