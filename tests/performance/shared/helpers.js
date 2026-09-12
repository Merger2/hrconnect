/**
 * Shared helpers for HRConnect K6 performance test suites.
 *
 * Usage in suite files:
 *   import { apiGet, apiPost, apiPut, apiDel, authHeaders } from '../shared/helpers.js';
 */

import http from 'k6/http';
import exec from 'k6/execution';
import { check } from 'k6';

// http_req_failed default hanya menganggap 2xx/3xx "sukses" — 403/422 bisnis
// (kuota cuti habis, dsb) ikut terhitung error padahal server sehat. Callback
// ini membuat metrik error mengukur KESEHATAN SERVER (5xx/gagal koneksi),
// bukan keputusan validasi bisnis. Breakdown per status tetap terlihat di
// report (K6-REPORT.md).
http.setResponseCallback(
  http.expectedStatuses(0, 200, 201, 202, 204, 302, 400, 401, 403, 404, 422, 429),
);

// ─── Configuration ─────────────────────────────────────────────

// Feature tag untuk metrik K6: `name.split('_')[0]`, dengan pemetaan khusus
// supaya konsisten dengan threshold per-suite (mis. {feature:kb-chat} untuk
// nama step kb_chat_* — 'kb_chat_rag_query'.split('_')[0] = 'kb').
export const featureTag = (name) =>
  ({ kb: 'kb-chat' })[name.split('_')[0]] ?? name.split('_')[0];

export const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
export const API = `${BASE_URL}/api/v1`;
// 3s: 3 VU berbagi 1 akun per role vs throttle API global 60 req/menit/user
// (heaviest suite ≈ 24 req/menit/akun). 0.5s (default lama) memicu 429 massal
// (temuan run 2026-09-06) — rate limit tidak boleh dilonggarkan demi test,
// jadi pacing-nya yang disesuaikan. K6_DURATION ikut naik ke 90s (run-all.sh)
// supaya semua step suite sempat tereksekusi.
export const THINK_TIME = parseFloat(__ENV.THINK_TIME) || 3;

// Sanctum personal access tokens (from K6PerformanceTestSeeder)
export const TOKENS = {
  employee: __ENV.K6_TOKEN_EMPLOYEE || '',
  admin:    __ENV.K6_TOKEN_ADMIN || '',
  manager:  __ENV.K6_TOKEN_MANAGER || '',
};

// Akun web (cookie session) per role — dipakai webGet via endpoint dev
// __e2e-login (services.e2e.login_token, hanya aktif di local/testing).
const SESSION_EMAILS = {
  employee: 'k6.employee@hrconnect.test',
  manager:  'k6.manager@hrconnect.test',
  finance:  'k6.fin@hrconnect.test',
  admin:    'k6.hr@hrconnect.test',
  superadmin: 'k6.admin@hrconnect.test',
};
const E2E_LOGIN_TOKEN = __ENV.E2E_LOGIN_TOKEN || 'local-apk-e2e';

// Cache sesi per role per VU — module scope k6 = context per VU.
const webSessions = {};

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

/**
 * Login via endpoint dev __e2e-login, kembalikan header Cookie.
 * Tag eksplisit feature=setup supaya call ini tidak mencemari metrik suite.
 */
function loginWeb(role) {
  const email = SESSION_EMAILS[role];
  if (!email) {
    throw new Error(`No web session email for role: ${role}`);
  }

  const login = http.get(
    `${BASE_URL}/__e2e-login?token=${encodeURIComponent(E2E_LOGIN_TOKEN)}&email=${encodeURIComponent(email)}&to=/home`,
    {
      redirects: 0,
      tags: { name: 'web_session_setup', feature: 'setup', role },
    },
  );

  // k6 menggabungkan header Set-Cookie ganda dengan '\n'
  const pairs = [];
  for (const line of (login.headers['Set-Cookie'] || '').split('\n')) {
    const m = line.match(/^\s*([^=;\s]+)=([^;]*)/);
    if (m && !pairs.some((p) => p.startsWith(`${m[1]}=`))) {
      pairs.push(`${m[1]}=${m[2]}`);
    }
  }

  const cookie = pairs.join('; ');
  if (!cookie) {
    console.warn(`[k6] Web session ${role} (VU ${exec.vu.idInTest}) kosong — ` +
      `endpoint __e2e-login tidak tersedia? Halaman web akan terukur 302 (check gagal).`);
  }
  return cookie;
}

/**
 * Sesi web per role per VU — cache; self-heal di webGet saat kadaluarsa.
 */
function ensureWebSession(role) {
  const key = `${role}:${exec.vu.idInTest}`;
  if (webSessions[key]) {
    return webSessions[key];
  }
  webSessions[key] = loginWeb(role);
  return webSessions[key];
}

// ─── Date helpers ──────────────────────────────────────────────

/**
 * Tanggal kerja berikutnya (skip Sabtu/Minggu) — kalender kerja 5 hari,
 * validator cuti menolak rentang 0 hari bila tanggal hanya weekend/libur.
 */
export function nextWorkday(daysFromNow = 14) {
  const d = new Date(Date.now() + daysFromNow * 86400000);
  while (d.getDay() === 0 || d.getDay() === 6) {
    d.setTime(d.getTime() + 86400000);
  }
  return d.toISOString().slice(0, 10);
}

/**
 * Tanggal kerja jauh ke depan yang bervariasi per VU & iterasi.
 * Pengajuan bersifat unik per tanggal (anti-overlap) dan kuota cuti tahunan
 * terbatas — tanggal deterministik yang sama pasti 422 di iterasi/run
 * berikutnya (bukti smoke 2026-09-07: "Tanggal bertabrakan").
 */
export function futureWorkday(minDays = 30) {
  const spread = (exec.vu.idInTest * 7 + exec.scenario.iterationInTest * 3) % 120;
  return nextWorkday(minDays + spread);
}

// ─── HTTP helpers ──────────────────────────────────────────────

/**
 * GET /api/v1/{path} — assert 200 + JSON.
 */
export function apiGet(path, name, role) {
  const res = http.get(`${API}${path}`, {
    headers: authHeaders(role),
    tags: { name, feature: featureTag(name), role },
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
 * GET page (HTML/Livewire) dengan sesi cookie per role.
 * - redirects:0 → redirect ke /login terlihat sebagai 302 = check gagal
 *   (tidak ada lagi pengukuran halaman login yang menyamar jadi 200).
 * - Self-heal: sesi kadaluarsa di tengah run (pola 3-ok/6-fail chunk-1
 *   2026-09-07) → re-login sekali lalu ulangi request aslinya.
 */
export function webGet(path, name, role) {
  const headers = { Accept: 'text/html' };
  if (role && role !== 'public') {
    headers.Cookie = ensureWebSession(role);
  }

  let res = http.get(`${BASE_URL}${path}`, {
    headers,
    redirects: 0,
    tags: { name, feature: featureTag(name), role: role || 'public' },
  });

  if (res.status !== 200 && role && role !== 'public') {
    const fresh = loginWeb(role);
    if (fresh) {
      webSessions[`${role}:${exec.vu.idInTest}`] = fresh;
      res = http.get(`${BASE_URL}${path}`, {
        headers: { Accept: 'text/html', Cookie: fresh },
        redirects: 0,
        tags: { name, feature: featureTag(name), role },
      });
    }
  }

  check(res, {
    [`${name}: HTTP 200 (bukan redirect)`]: (r) => r.status === 200,
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
    tags: { name, feature: featureTag(name), role },
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
    tags: { name, feature: featureTag(name), role },
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
    tags: { name, feature: featureTag(name), role },
  });

  check(res, {
    [`${name}: HTTP 2xx or 404`]: (r) =>
      (r.status >= 200 && r.status < 300) || r.status === 404,
  });

  return res;
}
