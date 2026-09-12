/**
 * K6 Suite 6: Reports
 *
 * Tests: dashboards, KB index, reports center, notifications.
 * Tag: feature=reports
 *
 * Run:
 *   k6 run --out json=tests/performance/results/reports.json tests/performance/suites/reports.js
 */

import { sleep, group } from 'k6';
import { apiGet, webGet, THINK_TIME } from '../shared/helpers.js';

const VUS = parseInt(__ENV.K6_VUS) || 3;
const DURATION = __ENV.K6_DURATION || '30s';

export const options = {
  scenarios: {
    reports_suite: {
      executor: 'constant-vus',
      vus: VUS,
      duration: DURATION,
    },
  },
  thresholds: {
    'http_req_duration{feature:reports}': ['p(95)<800', 'p(99)<1500'],
    'http_req_failed{feature:reports}': ['rate<0.05'],
    checks: ['rate>0.95'],
  },
};

export default function () {
  // ── 1. Employee dashboard ──
  group('reports: employee dashboard', () => {
    webGet('/home', 'reports_employee_dashboard', 'employee');
  });
  sleep(THINK_TIME);

  // ── 2. Admin dashboard (heavy query) ──
  group('reports: admin dashboard', () => {
    webGet('/admin/dashboard', 'reports_admin_dashboard', 'admin');
  });
  sleep(THINK_TIME);

  // ── 3. KB index (API) ──
  group('reports: kb index', () => {
    apiGet('/knowledge-base', 'reports_kb_index', 'employee');
  });
  sleep(THINK_TIME);

  // ── 4. KB detail ──
  group('reports: kb detail', () => {
    apiGet('/knowledge-base/1', 'reports_kb_detail', 'employee');
  });
  sleep(THINK_TIME);

  // ── 5. Notifications ──
  group('reports: notifications', () => {
    apiGet('/notifications', 'reports_notifications', 'employee');
  });
  sleep(THINK_TIME);

  // ── 6. Reports center page ──
  group('reports: reports center', () => {
    // Bukan /reports — route lama sudah pindah ke /admin/reports (404 di run lama)
    webGet('/admin/reports', 'reports_center', 'admin');
  });
  sleep(THINK_TIME);

  // ── 7. Admin analytics (superadmin-only page) ──
  group('reports: admin analytics', () => {
    webGet('/admin/analytics', 'reports_admin_analytics', 'superadmin');
  });
  sleep(THINK_TIME);

  // ── 8. Admin activity logs ──
  group('reports: admin activity logs', () => {
    webGet('/admin/activity-logs', 'reports_admin_activity_logs', 'admin');
  });
  sleep(THINK_TIME);

  // ── 9. Admin inbox (superadmin-only page) ──
  group('reports: admin inbox', () => {
    webGet('/admin/inbox', 'reports_admin_inbox', 'superadmin');
  });
  sleep(THINK_TIME);

  sleep(THINK_TIME * 2);
}
