/**
 * K6 Suite 8: Operations
 *
 * Tests: shift swap, WFH requests, document requests, HR checklists,
 *        cash advances (kasbon), announcements, collaboration.
 * Tag: feature=operations
 *
 * Run:
 *   k6 run --out json=tests/performance/results/operations.json tests/performance/suites/operations.js
 */

import { sleep, group } from 'k6';
import { webGet, THINK_TIME } from '../shared/helpers.js';

const VUS = parseInt(__ENV.K6_VUS) || 3;
const DURATION = __ENV.K6_DURATION || '30s';

export const options = {
  scenarios: {
    operations_suite: {
      executor: 'constant-vus',
      vus: VUS,
      duration: DURATION,
    },
  },
  thresholds: {
    'http_req_duration{feature:operations}': ['p(95)<800', 'p(99)<1500'],
    'http_req_failed{feature:operations}': ['rate<0.05'],
    checks: ['rate>0.95'],
  },
};

export default function () {
  // ── 1. Shift swap requests (employee) ──
  group('operations: shift swap', () => {
    webGet('/shift-swap-requests', 'operations_shift_swap', 'employee');
  });
  sleep(THINK_TIME);

  // ── 2. WFH requests (employee) ──
  group('operations: wfh requests', () => {
    webGet('/wfh-requests', 'operations_wfh', 'employee');
  });
  sleep(THINK_TIME);

  // ── 3. Document requests (employee) ──
  group('operations: document requests', () => {
    webGet('/document-requests', 'operations_doc_requests', 'employee');
  });
  sleep(THINK_TIME);

  // ── 4. HR tasks (employee) ──
  group('operations: hr tasks', () => {
    webGet('/hr-tasks', 'operations_hr_tasks', 'employee');
  });
  sleep(THINK_TIME);

  // ── 5. My tasks (employee) ──
  group('operations: my tasks', () => {
    webGet('/my-tasks', 'operations_my_tasks', 'employee');
  });
  sleep(THINK_TIME);

  // ── 6. My kasbon (employee) ──
  group('operations: my kasbon', () => {
    webGet('/my-kasbon', 'operations_my_kasbon', 'employee');
  });
  sleep(THINK_TIME);

  // ── 7. Collaboration inbox (employee) ──
  group('operations: collaboration', () => {
    webGet('/collaboration', 'operations_collaboration', 'employee');
  });
  sleep(THINK_TIME);

  // ── 8. My forms (employee) — route /my-forms lama sudah jadi /forms ──
  group('operations: my forms', () => {
    webGet('/forms', 'operations_my_forms', 'employee');
  });
  sleep(THINK_TIME);

  // ── 9. Notifications (employee) ──
  group('operations: notifications', () => {
    webGet('/notifications', 'operations_notifications', 'employee');
  });
  sleep(THINK_TIME);

  // ── 10. Admin HR checklists ──
  group('operations: admin checklists', () => {
    webGet('/admin/hr-checklists', 'operations_admin_checklists', 'admin');
  });
  sleep(THINK_TIME);

  // ── 11. Admin document requests ──
  group('operations: admin doc requests', () => {
    webGet('/admin/document-requests', 'operations_admin_doc_requests', 'admin');
  });
  sleep(THINK_TIME);

  // ── 12. Admin document templates ──
  group('operations: admin doc templates', () => {
    webGet('/admin/document-templates', 'operations_admin_doc_templates', 'admin');
  });
  sleep(THINK_TIME);

  // ── 13. Admin announcements ──
  group('operations: admin announcements', () => {
    webGet('/admin/announcements', 'operations_admin_announcements', 'admin');
  });
  sleep(THINK_TIME);

  // ── 14. Admin operations workspace ──
  group('operations: admin workspace', () => {
    webGet('/admin/operations', 'operations_admin_workspace', 'admin');
  });
  sleep(THINK_TIME);

  // ── 15. Admin collaboration ──
  group('operations: admin collaboration', () => {
    webGet('/admin/collaboration', 'operations_admin_collaboration', 'admin');
  });
  sleep(THINK_TIME);

  // ── 16. Admin custom forms ──
  group('operations: admin custom forms', () => {
    webGet('/admin/custom-forms', 'operations_admin_forms', 'admin');
  });
  sleep(THINK_TIME);

  // ── 17. Team kasbon (manager) ──
  group('operations: team kasbon', () => {
    webGet('/team-kasbon', 'operations_team_kasbon', 'manager');
  });
  sleep(THINK_TIME);

  // ── 18. Manager approvals ──
  group('operations: approvals', () => {
    webGet('/approvals', 'operations_approvals', 'manager');
  });
  sleep(THINK_TIME);

  // ── 19. Manager approvals history ──
  group('operations: approvals history', () => {
    webGet('/approvals/history', 'operations_approvals_history', 'manager');
  });
  sleep(THINK_TIME);

  // ── 20. Admin shift swap approvals ──
  group('operations: admin shift swaps', () => {
    webGet('/admin/shift-swaps', 'operations_admin_shift_swaps', 'admin');
  });
  sleep(THINK_TIME);

  sleep(THINK_TIME * 2);
}
