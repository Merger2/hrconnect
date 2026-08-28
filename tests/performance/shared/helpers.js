/**
 * Shared helpers for HRConnect K6 performance test suites.
 *
 * Usage in suite files:
 *   import { apiGet, apiPost, apiPut, apiDel, authHeaders } from '../shared/helpers.js';
 */

import http from 'k6/http';
import { check } from 'k6';

// ─── Configuration ─────────────────────────────────────────────

export const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
export const API = `${BASE_URL}/api/v1`;
export const THINK_TIME = parseFloat(__ENV.THINK_TIME) || 0.5;

// Sanctum personal access tokens (from K6PerformanceTestSeeder)
export const TOKENS = {
  employee: __ENV.K6_TOKEN_EMPLOYEE || '',
  admin:    __ENV.K6_TOKEN_ADMIN || '',
  manager:  __ENV.K6_TOKEN_MANAGER || '',
};

// ─── Auth ──────────────────────────────────────────────────────

/**
 * Build Bearer token headers for a role.
 */
export function authHeaders(role) {
  const token = TOKENS[role];
  if (!token) {
    throw new Error(`No token for role: ${role}. Run: php artisan db:seed --class=K6PerformanceTestSeeder`);
  }
  return {
    Authorization: `Bearer ${token}`,
    Accept: 'application/json',
  };
}

// ─── HTTP helpers ──────────────────────────────────────────────

/**
 * GET /api/v1/{path} — assert 200 + JSON.
 */
export function apiGet(path, name, role) {
  const res = http.get(`${API}${path}`, {
    headers: authHeaders(role),
    tags: { name, feature: name.split('_')[0], role },
  });

  check(res, {
    [`${name}: HTTP 2xx`]: (r) => r.status >= 200 && r.status < 300,
    [`${name}: is JSON`]: (r) => {
      try { JSON.parse(r.body); return true; } catch { return false; }
    },
  });

  return res;
}

/**
 * GET page (HTML) — for web/Livewire routes.
 */
export function webGet(path, name, role) {
  const res = http.get(`${BASE_URL}${path}`, {
    headers: { Accept: 'text/html' },
    tags: { name, feature: name.split('_')[0], role },
  });

  check(res, {
    [`${name}: HTTP 2xx`]: (r) => r.status >= 200 && r.status < 300,
    [`${name}: has HTML`]: (r) => r.body && r.body.includes('<'),
  });

  return res;
}

/**
 * POST /api/v1/{path} — assert 2xx or 4xx (validation).
 */
export function apiPost(path, body, name, role) {
  const res = http.post(`${API}${path}`, JSON.stringify(body), {
    headers: {
      ...authHeaders(role),
      'Content-Type': 'application/json',
    },
    tags: { name, feature: name.split('_')[0], role },
  });

  check(res, {
    // 2xx = success, 4xx = validation (still exercises pipeline)
    [`${name}: HTTP 2xx or 4xx`]: (r) => r.status >= 200 && r.status < 500,
    [`${name}: is JSON`]: (r) => {
      try { JSON.parse(r.body); return true; } catch { return false; }
    },
  });

  return res;
}

/**
 * PUT /api/v1/{path} — assert 2xx or 4xx.
 */
export function apiPut(path, body, name, role) {
  const res = http.put(`${API}${path}`, JSON.stringify(body), {
    headers: {
      ...authHeaders(role),
      'Content-Type': 'application/json',
    },
    tags: { name, feature: name.split('_')[0], role },
  });

  check(res, {
    [`${name}: HTTP 2xx or 4xx`]: (r) => r.status >= 200 && r.status < 500,
  });

  return res;
}

/**
 * DELETE /api/v1/{path} — assert 2xx or 404.
 */
export function apiDel(path, name, role) {
  const res = http.del(`${API}${path}`, null, {
    headers: authHeaders(role),
    tags: { name, feature: name.split('_')[0], role },
  });

  check(res, {
    [`${name}: HTTP 2xx or 404`]: (r) =>
      (r.status >= 200 && r.status < 300) || r.status === 404,
  });

  return res;
}
