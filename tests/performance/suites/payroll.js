/**
 * K6 Suite 5: Payroll
 *
 * Tests: payroll list, payslip, reimbursement, admin payroll views.
 * Tag: feature=payroll
 *
 * Run:
 *   k6 run --out json=tests/performance/results/payroll.json tests/performance/suites/payroll.js
 */

import { sleep, group, check } from 'k6';
import http from 'k6/http';
import { apiGet, apiPost, apiPut, webGet, THINK_TIME, authHeaders, API } from '../shared/helpers.js';

const VUS = parseInt(__ENV.K6_VUS) || 3;
// Receipt fixture — open() hanya valid di init scope (top-level) k6
const RECEIPT_FILE = open('../fixtures/receipt.png', 'b');
// ID reimbursement & payroll dari response terakhir VU ini (id hardcode = 404)
let lastReimbId = 0;
let lastPayrollId = 0;
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

  // ── 2. Payroll detail (admin) — ambil ID nyata dari list admin dulu ──
  group('payroll: detail', () => {
    const list = apiGet('/payrolls', 'payroll_admin_list', 'admin');
    if (list.status >= 200 && list.status < 300) {
      try {
        const body = list.json();
        const first = body?.data?.[0]?.id ?? body?.data?.data?.[0]?.id ?? 0;
        if (first) lastPayrollId = first;
      } catch { /* keep previous */ }
    }
    if (lastPayrollId > 0) {
      apiGet(`/payrolls/${lastPayrollId}`, 'payroll_detail', 'admin');
    }
  });
  sleep(THINK_TIME);

  // ── 3. Payslip — endpoint stream PDF (bukan JSON); skip bila tak ada data ──
  group('payroll: payslip', () => {
    if (lastPayrollId > 0) {
      const res = http.get(`${API}/payrolls/${lastPayrollId}/payslip`, {
        headers: authHeaders('admin'),
        tags: { name: 'payroll_payslip', feature: 'payroll', role: 'admin' },
      });
      check(res, {
        'payroll_payslip: HTTP 2xx (stream)': (r) => r.status >= 200 && r.status < 300,
      });
    }
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

  // ── 7. Admin payroll settings DIHAPUS: /admin/payrolls/settings adalah
  //   halaman finance (manage_payroll_settings tidak di-seed ke role admin/HR
  //   — konvensi seeder). 403 = perilaku aplikasi yang benar, bukan lag. ──

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

  // ── 10. Reimbursement submit — multipart + receipt (receipt WAJIB file
  //   upload: StoreReimbursementRequest + SecureUploadPolicy) ──
  group('payroll: reimbursement submit', () => {
    const res = http.post(`${API}/reimbursements`, {
      category_id: '1',
      amount: '75000',
      description: 'K6 load test reimbursement submit for performance benchmark test',
      expense_date: new Date().toISOString().slice(0, 10),
      receipt: http.file(RECEIPT_FILE, 'receipt.png', 'image/png'),
    }, {
      headers: authHeaders('employee'),
      tags: { name: 'payroll_reimbursement_submit', feature: 'payroll', role: 'employee' },
    });

    if (res.status >= 200 && res.status < 300) {
      try { lastReimbId = res.json('data.id') || 0; } catch { /* tetap 0 */ }
    }
  });
  sleep(THINK_TIME);

  // ── 11. Reimbursement show (admin) — pakai ID nyata dari submit di atas
  //   (id 1 hardcode = 404: fixture tidak menjamin id kecil ada) ──
  group('payroll: reimbursement detail', () => {
    if (lastReimbId > 0) {
      apiGet(`/reimbursements/${lastReimbId}`, 'payroll_reimbursement_detail', 'admin');
    } else {
      apiGet('/reimbursements', 'payroll_reimbursement_detail_fallback', 'admin');
    }
  });
  sleep(THINK_TIME);

  // ── 12. Employee reimbursement page (Livewire) ──
  group('payroll: employee reimbursement page', () => {
    webGet('/reimbursement', 'payroll_employee_reimbursement', 'employee');
  });
  sleep(THINK_TIME * 2);
}
