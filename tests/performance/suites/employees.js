/**
 * K6 Suite 2: Employees
 *
 * Tests: employee list/show, master data (branches, divisions, positions, wilayah).
 * Tag: feature=employees
 *
 * Run:
 *   k6 run --out json=tests/performance/results/employees.json tests/performance/suites/employees.js
 */

import { sleep, group } from 'k6';
import { apiGet, webGet, THINK_TIME } from '../shared/helpers.js';

const VUS = parseInt(__ENV.K6_VUS) || 3;
const DURATION = __ENV.K6_DURATION || '30s';

export const options = {
  scenarios: {
    employees_suite: {
      executor: 'constant-vus',
      vus: VUS,
      duration: DURATION,
    },
  },
  thresholds: {
    'http_req_duration{feature:employees}': ['p(95)<500', 'p(99)<1000'],
    'http_req_failed{feature:employees}': ['rate<0.05'],
    checks: ['rate>0.95'],
  },
};

export default function () {
  // ── 1. Employee list (admin) ──
  group('employees: list', () => {
    apiGet('/employees', 'employees_list', 'admin');
  });
  sleep(THINK_TIME);

  // ── 2. Employee self ──
  group('employees: self', () => {
    apiGet('/employees/me', 'employees_self', 'employee');
  });
  sleep(THINK_TIME);

  // ── 3. Employee detail ──
  group('employees: detail', () => {
    apiGet('/employees/1', 'employees_detail', 'admin');
  });
  sleep(THINK_TIME);

  // ── 4. Branches ──
  group('employees: branches', () => {
    apiGet('/branches', 'employees_branches', 'admin');
  });
  sleep(THINK_TIME);

  // ── 5. Divisions ──
  group('employees: divisions', () => {
    apiGet('/divisions', 'employees_divisions', 'admin');
  });
  sleep(THINK_TIME);

  // ── 6. Positions ──
  group('employees: positions', () => {
    apiGet('/positions', 'employees_positions', 'admin');
  });
  sleep(THINK_TIME);

  // ── 7. Wilayah provinces ──
  group('employees: wilayah', () => {
    apiGet('/wilayah/provinces', 'employees_wilayah', 'admin');
  });
  sleep(THINK_TIME);

  // ── 8. Admin employees page (Livewire) ──
  group('employees: admin page', () => {
    webGet('/admin/employees', 'employees_admin_page', 'admin');
  });
  sleep(THINK_TIME);

  // ── 9. Admin master data pages ──
  group('employees: master division', () => {
    webGet('/admin/masterdata/division', 'employees_master_division', 'admin');
  });
  sleep(THINK_TIME);

  group('employees: master job-title', () => {
    webGet('/admin/masterdata/job-title', 'employees_master_job_title', 'admin');
  });
  sleep(THINK_TIME);

  group('employees: master shift', () => {
    webGet('/admin/masterdata/shift', 'employees_master_shift', 'admin');
  });
  sleep(THINK_TIME);

  sleep(THINK_TIME * 2);
}
