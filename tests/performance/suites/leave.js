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
import { apiGet, apiPost, webGet, THINK_TIME } from '../shared/helpers.js';

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

  // ── 2. Submit leave (may 422 if no entitlement) ──
  group('leave: submit', () => {
    const nextWeek = new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 10);
    apiPost('/leaves', {
      leave_type_id: 1,
      start_date: nextWeek,
      end_date: nextWeek,
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
    const tomorrow = new Date(Date.now() + 86400000).toISOString().slice(0, 10);
    apiPost('/overtimes', {
      date: tomorrow,
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
