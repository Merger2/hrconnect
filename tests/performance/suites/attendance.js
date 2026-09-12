/**
 * K6 Suite 3: Attendance
 *
 * Tests: clock-in page, attendance history, schedule, admin attendance views.
 * Tag: feature=attendance
 *
 * Run:
 *   k6 run --out json=tests/performance/results/attendance.json tests/performance/suites/attendance.js
 */

import { sleep, group } from 'k6';
import { webGet, THINK_TIME } from '../shared/helpers.js';

const VUS = parseInt(__ENV.K6_VUS) || 3;
const DURATION = __ENV.K6_DURATION || '30s';

export const options = {
  scenarios: {
    attendance_suite: {
      executor: 'constant-vus',
      vus: VUS,
      duration: DURATION,
    },
  },
  thresholds: {
    'http_req_duration{feature:attendance}': ['p(95)<800', 'p(99)<1500'],
    'http_req_failed{feature:attendance}': ['rate<0.05'],
    checks: ['rate>0.95'],
  },
};

export default function () {
  // ── 1. Clock-in page (Livewire render only, no face action) ──
  group('attendance: clock-in page', () => {
    webGet('/scan', 'attendance_clockin_page', 'employee');
  });
  sleep(THINK_TIME);

  // ── 2. Attendance history ──
  group('attendance: history', () => {
    webGet('/attendance-history', 'attendance_history', 'employee');
  });
  sleep(THINK_TIME);

  // ── 3. My schedule ──
  group('attendance: my schedule', () => {
    webGet('/my-schedule', 'attendance_my_schedule', 'employee');
  });
  sleep(THINK_TIME);

  // ── 4. Attendance corrections ──
  group('attendance: corrections', () => {
    webGet('/attendance-corrections', 'attendance_corrections', 'employee');
  });
  sleep(THINK_TIME);

  // ── 5. Admin attendances list ──
  group('attendance: admin list', () => {
    webGet('/admin/attendances', 'attendance_admin_list', 'admin');
  });
  sleep(THINK_TIME);

  // ── 6. Admin attendance report ──
  group('attendance: admin report', () => {
    // Bukan /admin/attendances/report — itu endpoint export (wajib param tanggal,
    // tanpa param redirect()->back() yang tak stabil). Report Center = /admin/reports.
    webGet('/admin/reports', 'attendance_admin_report', 'admin');
  });
  sleep(THINK_TIME);

  // ── 7. Admin schedules ──
  group('attendance: admin schedules', () => {
    webGet('/admin/schedules', 'attendance_admin_schedules', 'admin');
  });
  sleep(THINK_TIME);

  // ── 8. Admin attendance corrections ──
  group('attendance: admin corrections', () => {
    webGet('/admin/attendance-corrections', 'attendance_admin_corrections', 'admin');
  });
  sleep(THINK_TIME);

  // ── 9. Face enrollment page ──
  group('attendance: face enrollment', () => {
    webGet('/face-enrollment', 'attendance_face_enrollment', 'employee');
  });
  sleep(THINK_TIME);

  // ── 10. Admin holidays ──
  group('attendance: admin holidays', () => {
    webGet('/admin/holidays', 'attendance_admin_holidays', 'admin');
  });
  sleep(THINK_TIME);

  // ── 11. Employee leave page ──
  group('attendance: apply leave page', () => {
    webGet('/apply-leave', 'attendance_apply_leave', 'employee');
  });
  sleep(THINK_TIME * 2);
}
