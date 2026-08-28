/**
 * K6 Suite 1: Auth
 *
 * Tests: login, profile read/update, password change.
 * Tag: feature=auth
 *
 * Run:
 *   k6 run --out json=tests/performance/results/auth.json tests/performance/suites/auth.js
 */

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { API, authHeaders, THINK_TIME } from '../shared/helpers.js';

const VUS = parseInt(__ENV.K6_VUS) || 3;
const DURATION = __ENV.K6_DURATION || '30s';

export const options = {
  scenarios: {
    auth_suite: {
      executor: 'constant-vus',
      vus: VUS,
      duration: DURATION,
    },
  },
  thresholds: {
    'http_req_duration{feature:auth}': ['p(95)<500', 'p(99)<1000'],
    'http_req_failed{feature:auth}': ['rate<0.05'],
    checks: ['rate>0.95'],
  },
};

export default function () {
  // ── READ operations (fast,测 throughput) ──
  // Token verify
  group('auth: token verify', () => {
    const res = http.get(`${API}/user`, {
      headers: authHeaders('employee'),
      tags: { name: 'auth_token_verify', feature: 'auth' },
    });
    check(res, {
      'auth_token_verify: HTTP 200': (r) => r.status === 200,
    });
  });
  sleep(THINK_TIME);

  // Profile read
  group('auth: profile read', () => {
    const res = http.get(`${API}/profile`, {
      headers: authHeaders('employee'),
      tags: { name: 'auth_profile_read', feature: 'auth' },
    });
    check(res, {
      'auth_profile_read: HTTP 200': (r) => r.status === 200,
    });
  });
  sleep(THINK_TIME);

  // ── WRITE operations (slower, bcrypt + DB writes — expect higher latency) ──
  // Profile update (DB write + 5 eager loads)
  group('auth: profile update', () => {
    const res = http.put(`${API}/profile`, JSON.stringify({
      phone: '081900000101',
    }), {
      headers: {
        ...authHeaders('employee'),
        'Content-Type': 'application/json',
      },
      tags: { name: 'auth_profile_update', feature: 'auth' },
    });
    check(res, {
      'auth_profile_update: HTTP 2xx': (r) => r.status >= 200 && r.status < 300,
    });
  });
  sleep(THINK_TIME);

  // Password change (2× bcrypt + DB write)
  group('auth: password change', () => {
    const res = http.post(`${API}/profile/password`, JSON.stringify({
      current_password: 'password',
      password: 'password',
      password_confirmation: 'password',
    }), {
      headers: {
        ...authHeaders('employee'),
        'Content-Type': 'application/json',
      },
      tags: { name: 'auth_password_change', feature: 'auth' },
    });
    check(res, {
      'auth_password_change: HTTP 2xx or 4xx': (r) => r.status >= 200 && r.status < 500,
    });
  });
  sleep(THINK_TIME);

  // Verify read still fast after writes
  group('auth: profile read post-write', () => {
    const res = http.get(`${API}/profile`, {
      headers: authHeaders('employee'),
      tags: { name: 'auth_profile_read_post', feature: 'auth' },
    });
    check(res, {
      'auth_profile_read_post: HTTP 200': (r) => r.status === 200,
    });
  });
  sleep(THINK_TIME * 2);
}
