/**
 * K6 Suite 9: Assets
 *
 * Tests: asset CRUD (API), my-assets (web), appraisals (web).
 * Tag: feature=assets
 *
 * Run:
 *   k6 run --out json=tests/performance/results/assets.json tests/performance/suites/assets.js
 */

import { sleep, group } from 'k6';
import { apiGet, webGet, THINK_TIME } from '../shared/helpers.js';
// Catatan: halaman appraisal & import-export dijaga permission superadmin-only
// (tidak di-seed ke role admin/HR — konvensi RoleAndPermissionSeeder),
// jadi webGet halaman tsb memakai sesi superadmin.

const VUS = parseInt(__ENV.K6_VUS) || 3;
const DURATION = __ENV.K6_DURATION || '30s';

export const options = {
  scenarios: {
    assets_suite: {
      executor: 'constant-vus',
      vus: VUS,
      duration: DURATION,
    },
  },
  thresholds: {
    'http_req_duration{feature:assets}': ['p(95)<500', 'p(99)<1000'],
    'http_req_failed{feature:assets}': ['rate<0.05'],
    checks: ['rate>0.95'],
  },
};

// ID asset dari list, dipakai step detail (id 1 hardcode = 404)
let lastAssetId = 0;

export default function () {
  // ── 1. Assets list (admin API) ──
  group('assets: list', () => {
    const res = apiGet('/assets', 'assets_list', 'admin');
    if (res.status >= 200 && res.status < 300) {
      try {
        const body = res.json();
        const first = body?.data?.[0]?.id ?? body?.data?.data?.[0]?.id ?? 0;
        if (first) lastAssetId = first;
      } catch { /* keep previous */ }
    }
  });
  sleep(THINK_TIME);

  // ── 2. Asset detail (admin API) — ID nyata dari list ──
  group('assets: detail', () => {
    if (lastAssetId > 0) {
      apiGet(`/assets/${lastAssetId}`, 'assets_detail', 'admin');
    } else {
      apiGet('/assets', 'assets_detail_fallback', 'admin');
    }
  });
  sleep(THINK_TIME);

  // ── 3. My assets (employee web) ──
  group('assets: my assets', () => {
    webGet('/my-assets', 'assets_my_assets', 'employee');
  });
  sleep(THINK_TIME);

  // ── 4. Admin assets page ──
  group('assets: admin page', () => {
    webGet('/admin/assets', 'assets_admin_page', 'admin');
  });
  sleep(THINK_TIME);

  // ── 5. My performance (employee web) ──
  group('assets: my performance', () => {
    webGet('/my-performance', 'assets_my_performance', 'employee');
  });
  sleep(THINK_TIME);

  // ── 6. Admin appraisals page (superadmin-only) ──
  group('assets: admin appraisals', () => {
    webGet('/admin/appraisals', 'assets_admin_appraisals', 'superadmin');
  });
  sleep(THINK_TIME);

  // ── 7. Admin settings ──
  group('assets: admin settings', () => {
    webGet('/admin/settings', 'assets_admin_settings', 'admin');
  });
  sleep(THINK_TIME);

  // ── 8. Admin companies ──
  group('assets: admin companies', () => {
    webGet('/admin/companies', 'assets_admin_companies', 'admin');
  });
  sleep(THINK_TIME);

  // ── 9. Admin roles ──
  group('assets: admin roles', () => {
    webGet('/admin/roles-permissions', 'assets_admin_roles', 'admin');
  });
  sleep(THINK_TIME);

  // ── 10. Admin user sessions ──
  group('assets: admin sessions', () => {
    webGet('/admin/user-sessions', 'assets_admin_sessions', 'admin');
  });
  sleep(THINK_TIME);

  // ── 11. Admin import-export users (superadmin-only) ──
  group('assets: admin import users', () => {
    webGet('/admin/import-export/users', 'assets_admin_import_users', 'superadmin');
  });
  sleep(THINK_TIME);

  // ── 12. Admin import-export attendances (superadmin-only) ──
  group('assets: admin import attendance', () => {
    webGet('/admin/import-export/attendances', 'assets_admin_import_attendance', 'superadmin');
  });
  sleep(THINK_TIME);

  sleep(THINK_TIME * 2);
}
