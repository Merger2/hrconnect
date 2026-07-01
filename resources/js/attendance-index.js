export default function () {
    return {
        records: [],
        period: '',
        loading: true,
        today: { has_clocked_in: false, has_clocked_out: false, attendance: null },
        todayStatus: 'incomplete',
        todayFormatted: '',
        summary: { days_worked: 0, late_count: 0, absent_count: 0 },

        init() {
            this.todayFormatted = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            this.period = this.currentPeriod();
            Promise.all([this.fetchToday(), this.fetchAttendance()]);
        },

        currentPeriod() {
            const d = new Date();
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        },

        lastMonthPeriod() {
            const d = new Date();
            d.setMonth(d.getMonth() - 1);
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        },

        async fetchToday() {
            try {
                const res = await fetch('/api/v1/attendance/today', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.today = json.data;
                    this.todayStatus = (this.today.has_clocked_in && this.today.has_clocked_out) ? 'complete' : 'incomplete';
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Gagal memuat data hari ini' });
            }
        },

        async fetchAttendance() {
            this.loading = true;
            try {
                const res = await fetch(`/api/v1/attendance?period=${this.period}&per_page=50`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const json = await res.json();
                if (json.status === 'success') {
                    this.records = json.data;
                    this.calcSummary();
                }
            } catch {
                Livewire.dispatch('toast', { variant: 'error', text: 'Gagal memuat riwayat absensi' });
            }
            finally { this.loading = false; }
        },

        calcSummary() {
            const present = this.records.filter(r => r.status === 'present' || r.status === 'late' || r.status === 'wfa').length;
            const late = this.records.filter(r => r.status === 'late').length;
            const absent = this.records.filter(r => r.status === 'absent').length;
            this.summary = { days_worked: present, late_count: late, absent_count: absent };
        },

        statusBorder(status) {
            const map = {
                present: 'border-l-4 border-l-success',
                late: 'border-l-4 border-l-warning',
                absent: 'border-l-4 border-l-error',
                wfa: 'border-l-4 border-l-info',
            };
            return map[status] || 'border-l-4 border-l-outline-variant';
        },

        statusClasses(status) {
            const map = {
                present: 'bg-success/10 text-success ring-success/30',
                late: 'bg-warning/10 text-warning ring-warning/30',
                absent: 'bg-error/10 text-error ring-error/30',
                wfa: 'bg-info/10 text-info ring-info/30',
            };
            return map[status] || '';
        },

        statusLabel(status) {
            const labels = { present: 'Hadir', late: 'Terlambat', absent: 'Absen', wfa: 'WFA' };
            return labels[status] || status;
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        formatTime(timeStr) {
            if (!timeStr) return null;
            const d = new Date(timeStr);
            return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },

        formatTimeSimple(timeStr) {
            if (!timeStr) return null;
            const d = new Date(timeStr);
            return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },
    };
}
