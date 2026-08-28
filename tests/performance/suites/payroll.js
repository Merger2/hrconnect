/**
 * K6 Suite 5: Payroll
 *
 * Tests: payroll list, payslip, reimbursement, admin payroll views.
 * Tag: feature=payroll
 *
 * Run:
 *   k6 run --out json=tests/performance/results/payroll.json tests/performance/suites/payroll.js
 */

import { sleep, group } from 'k6';
import { apiGet, apiPost, apiPut, webGet, THINK_TIME } from '../shared/helpers.js';

const VUS = parseInt(__ENV.K6_VUS) || 3;
const DURATION = __ENV.K6_DURATION || '30s';

export const options = {
  scenarios: {
    payroll_suite: {
      executor: 'constant-vus',
      vus: VUS,
      duration: DURATION,
    },
  },
  thresholds: {
    'http_req_duration{feature:payroll}': ['p(95)<500', 'p(99)<1000'],
    'http_req_failed{feature:payroll}': ['rate<0.05'],
    checks: ['rate>0.95'],
  },
};

export default function () {
  // ── 1. Employee payroll list (API) ──
  group('payroll: list', () => {
    apiGet('/payrolls', 'payroll_list', 'employee');
  });
  sleep(THINK_TIME);

  // ── 2. Payroll detail (admin) ──
  group('payroll: detail', () => {
    apiGet('/payrolls/1', 'payroll_detail', 'admin');
  });
  sleep(THINK_TIME);

  // ── 3. Payslip ──
  group('payroll: payslip', () => {
    apiGet('/payrolls/1/payslip', 'payroll_payslip', 'admin');
  });
  sleep(THINK_TIME);

  // ── 4. Employee payslip page (Livewire) ──
  group('payroll: employee page', () => {
    webGet('/payroll', 'payroll_employee_page', 'employee');
  });
  sleep(THINK_TIME);

  // ── 5. Reimbursement list ──
  group('payroll: reimbursement list', () => {
    apiGet('/reimbursements', 'payroll_reimbursement_list', 'employee');
  });
  sleep(THINK_TIME);

  // ── 6. Admin payroll page ──
  group('payroll: admin page', () => {
    webGet('/admin/payrolls', 'payroll_admin_page', 'admin');
  });
  sleep(THINK_TIME);

  // ── 7. Admin payroll settings ──
  group('payroll: admin settings', () => {
    webGet('/admin/payrolls/settings', 'payroll_admin_settings', 'admin');
  });
  sleep(THINK_TIME);

  // ── 8. Admin reimbursements ──
  group('payroll: admin reimbursements', () => {
    webGet('/admin/reimbursements', 'payroll_admin_reimbursements', 'admin');
  });
  sleep(THINK_TIME);

  // ── 9. Admin cash advances ──
  group('payroll: admin kasbon', () => {
    webGet('/admin/manage-kasbon', 'payroll_admin_kasbon', 'admin');
  });
  sleep(THINK_TIME);

  // ── 10. Reimbursement submit (may 422: receipt required) ──
  group('payroll: reimbursement submit', () => {
    apiPost('/reimbursements', {
      category_id: 1,
      amount: 75000,
      description: 'K6 load test reimbursement submit for performance benchmark test',
      expense_date: new Date().toISOString().slice(0, 10),
    }, 'payroll_reimbursement_submit', 'employee');
  });
  sleep(THINK_TIME);

  // ── 11. Reimbursement show (admin) ──
  group('payroll: reimbursement detail', () => {
    apiGet('/reimbursements/1', 'payroll_reimbursement_detail', 'admin');
  });
  sleep(THINK_TIME);

  // ── 12. Employee reimbursement page (Livewire) ──
  group('payroll: employee reimbursement page', () => {
    webGet('/reimbursement', 'payroll_employee_reimbursement', 'employee');
  });
  sleep(THINK_TIME * 2);
}
