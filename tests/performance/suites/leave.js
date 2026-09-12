/**
 * K6 Suite 4: Leave
 *
 * Tests: leave list, overtime, approval queue, admin leave/overtime views.
 * Tag: feature=leave
 *
 * Run:
 *   k6 run --out json=tests/performance/results/leave.json tests/performance/suites/leave.js
 */

import { sleep, group } from 'k6';
import { apiGet, apiPost, webGet, THINK_TIME, nextWorkday, futureWorkday } from '../shared/helpers.js';

const VUS = parseInt(__ENV.K6_VUS) || 3;
const DURATION = __ENV.K6_DURATION || '30s';

export const options = {
  scenarios: {
    leave_suite: {
      executor: 'constant-vus',
      vus: VUS,
      duration: DURATION,
    },
  },
  thresholds: {
    'http_req_duration{feature:leave}': ['p(95)<500', 'p(99)<1000'],
    'http_req_failed{feature:leave}': ['rate<0.10'],  // allow higher for 422 validation
    checks: ['rate>0.90'],
  },
};

export default function () {
  // ── 1. Employee leave list ──
  group('leave: list', () => {
    apiGet('/leaves', 'leave_list', 'employee');
  });
  sleep(THINK_TIME);

  // ── 2. Submit leave (hari kerja — kalender 5 hari menolak rentang weekend) ──
  group('leave: submit', () => {
    // futureWorkday: unik per VU/iterasi — validator menolak tanggal yang
    // overlap dgn pengajuan cuti lain (state-dependent, bukan bug)
    const start = futureWorkday();
    // Rentang 1 hari kerja; kalau start Jumat, end Senin (durasi 1 hari kerja)
    const startD = new Date(`${start}T00:00:00Z`);
    const endD = new Date(startD.getTime() + (startD.getUTCDay() === 5 ? 3 : 1) * 86400000);
    const end = endD.toISOString().slice(0, 10);
    apiPost('/leaves', {
      leave_type_id: 1,
      start_date: start,
      end_date: end,
      day_type: 'full_day',
      reason: 'K6 load test leave request for performance benchmark',
    }, 'leave_submit', 'employee');
  });
  sleep(THINK_TIME);

  // ── 3. Overtime list ──
  group('leave: overtime list', () => {
    apiGet('/overtimes', 'leave_overtime_list', 'employee');
  });
  sleep(THINK_TIME);

  // ── 4. Submit overtime ──
  group('leave: overtime submit', () => {
    // Tanggal unik per VU/iterasi: kuota lembur 18 jam/minggu menolak
    // tanggal yang sama terus-menerus (state-dependent)
    apiPost('/overtimes', {
      date: futureWorkday(),
      start_time: '18:00',
      end_time: '20:00',
      description: 'K6 load test overtime request for performance benchmark',
    }, 'leave_overtime_submit', 'employee');
  });
  sleep(THINK_TIME);

  // ── 5. Approval queue ──
  group('leave: approvals', () => {
    apiGet('/approvals', 'leave_approvals', 'manager');
  });
  sleep(THINK_TIME);

  // ── 6. Admin leave page ──
  group('leave: admin leaves', () => {
    webGet('/admin/leaves', 'leave_admin_leaves', 'admin');
  });
  sleep(THINK_TIME);

  // ── 7. Admin overtime page ──
  group('leave: admin overtime', () => {
    webGet('/admin/overtime', 'leave_admin_overtime', 'admin');
  });
  sleep(THINK_TIME);

  // ── 8. Admin shift swaps ──
  group('leave: admin shift swaps', () => {
    webGet('/admin/shift-swaps', 'leave_admin_shift_swaps', 'admin');
  });
  sleep(THINK_TIME);

  // ── 9. Employee apply leave page ──
  group('leave: apply page', () => {
    webGet('/apply-leave', 'leave_apply_page', 'employee');
  });
  sleep(THINK_TIME);

  sleep(THINK_TIME * 2);
}
