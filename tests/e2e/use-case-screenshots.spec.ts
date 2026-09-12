/**
 * HRConnect Use-Case Screenshot Suite (35 UC inti dari 42 use case Release 1)
 *
 * Katalog final — tiap use case = 1 screenshot inti:
 *  - UC01–13 (Login + Employee/ESS): desktop + mobile
 *  - UC14–35 (Manager/Finance/Admin/Superadmin): desktop saja
 *  - UC16 (Approval Manager) = 1 halaman dengan state tab berbeda
 *    (cuti/lembur/reimbursement/wfh) sebagai bukti kategori + aksi.
 *
 * Use case proses otomatis TIDAK dipotret terpisah (terlihat di layar lain):
 *  - Validasi GPS & face recognition → screenshot UC03 (presensi)
 *  - Hitung PPh21 TER / BPJS / generate payslip → UC17 + UC12
 *  - Approval cuti/lembur/reimb/WFH L1 → UC16
 *
 * Output: screenshots/use-cases/UC{nn}-{nama}[-mobile].png (flat, siap copy)
 *
 * PENTING (lesson 2026-09-06): tiap halaman DIVERIFIKASI dulu sebelum
 * dianggap sukses — status <400, tidak redirect ke /login, tidak render
 * error page. Auth state per-role file (manager.json/finance.json berbeda
 * dari employee.json — regenerasi via project `setup`).
 */
import { expect, test, type Page } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const BASE_URL = process.env.APP_URL || 'http://localhost:8000';
const AUTH_DIR = path.join(__dirname, '.auth');
const OUT_DIR = path.join(process.cwd(), 'screenshots', 'use-cases');

type Role = 'public' | 'employee' | 'manager' | 'finance' | 'admin';

interface UcShot {
  uc: string;
  name: string;
  route: string;
  role: Role;
  mobile: boolean;
}

// ============================================================
// KATALOG 35 USE CASE
// ============================================================

const SHOTS: UcShot[] = [
  // --- Umum (public) ---
  { uc: 'UC01', name: 'login-page', route: '/login', role: 'public', mobile: true },

  // --- Employee / ESS (desktop + mobile) ---
  { uc: 'UC02', name: 'employee-dashboard', route: '/home', role: 'employee', mobile: true },
  { uc: 'UC03', name: 'employee-presensi-gps-face', route: '/scan', role: 'employee', mobile: true },
  { uc: 'UC04', name: 'employee-face-enrollment', route: '/face-enrollment', role: 'employee', mobile: true },
  { uc: 'UC05', name: 'employee-riwayat-presensi', route: '/attendance-history', role: 'employee', mobile: true },
  { uc: 'UC06', name: 'employee-koreksi-presensi', route: '/attendance-corrections', role: 'employee', mobile: true },
  { uc: 'UC07', name: 'employee-pengajuan-cuti', route: '/apply-leave', role: 'employee', mobile: true },
  { uc: 'UC08', name: 'employee-pengajuan-lembur', route: '/overtime', role: 'employee', mobile: true },
  { uc: 'UC09', name: 'employee-pengajuan-reimbursement', route: '/reimbursement', role: 'employee', mobile: true },
  { uc: 'UC10', name: 'employee-pengajuan-wfh', route: '/wfh-requests', role: 'employee', mobile: true },
  { uc: 'UC11', name: 'employee-jadwal-kerja', route: '/my-schedule', role: 'employee', mobile: true },
  { uc: 'UC12', name: 'employee-payslip', route: '/payroll', role: 'employee', mobile: true },
  { uc: 'UC13', name: 'employee-kb-chat', route: '/knowledge-base/chat', role: 'employee', mobile: true },

  // --- Manager (desktop saja) ---
  // /admin/employees memakai managedBy(auth()->user()) → untuk manager otomatis
  // ter-scope ke tim langsungnya (bukan semua karyawan).
  { uc: 'UC14', name: 'manager-data-karyawan-tim', route: '/admin/employees', role: 'manager', mobile: false },
  // UC15: info kehadiran & pengajuan tim = tab Team Attendance di halaman approval.
  // (Bukan /admin/inbox — halaman itu butuh permission admin, 403 untuk manager.)
  { uc: 'UC15', name: 'manager-kehadiran-pengajuan-tim', route: '/approvals?activeTab=attendance-corrections', role: 'manager', mobile: false },
  // UC16: satu halaman approval, 4 state kategori sebagai bukti (mewakili UC 15–18 lama).
  { uc: 'UC16', name: 'manager-approval-cuti', route: '/approvals?activeTab=leaves', role: 'manager', mobile: false },
  { uc: 'UC16', name: 'manager-approval-lembur', route: '/approvals?activeTab=overtimes', role: 'manager', mobile: false },
  { uc: 'UC16', name: 'manager-approval-reimbursement', route: '/approvals?activeTab=reimbursements', role: 'manager', mobile: false },
  { uc: 'UC16', name: 'manager-approval-wfh', route: '/approvals?activeTab=wfh', role: 'manager', mobile: false },

  // --- Finance (desktop saja) ---
  // UC17 mewakili hitung PPh21 TER + BPJS + generate payslip (komponen terlihat di list/detail).
  { uc: 'UC17', name: 'finance-payroll-komponen', route: '/admin/payrolls', role: 'finance', mobile: false },
  { uc: 'UC18', name: 'finance-konfigurasi-pajak-bpjs', route: '/admin/payrolls/settings', role: 'finance', mobile: false },
  { uc: 'UC19', name: 'finance-approval-reimbursement-l2', route: '/admin/reimbursements', role: 'finance', mobile: false },

  // --- Admin (desktop saja) ---
  { uc: 'UC20', name: 'admin-daftar-data-karyawan', route: '/admin/employees', role: 'admin', mobile: false },
  { uc: 'UC21', name: 'admin-form-tambah-karyawan', route: '/admin/employees/create', role: 'admin', mobile: false },
  // UC22: master data (navigasi divisi/jabatan/shift) — divisi dipilih sebagai wakil.
  { uc: 'UC22', name: 'admin-master-data-divisi', route: '/admin/masterdata/division', role: 'admin', mobile: false },
  { uc: 'UC23', name: 'admin-kelola-presensi', route: '/admin/attendances', role: 'admin', mobile: false },
  { uc: 'UC24', name: 'admin-impor-ekspor', route: '/admin/import-export/users', role: 'admin', mobile: false },
  { uc: 'UC25', name: 'admin-approval-cuti-l2', route: '/admin/leaves', role: 'admin', mobile: false },
  { uc: 'UC26', name: 'admin-approval-lembur-l2', route: '/admin/overtime', role: 'admin', mobile: false },
  { uc: 'UC27', name: 'admin-jenis-cuti-kuota', route: '/admin/masterdata/leave-types', role: 'admin', mobile: false },
  { uc: 'UC28', name: 'admin-hari-libur', route: '/admin/holidays', role: 'admin', mobile: false },
  { uc: 'UC29', name: 'admin-kelola-knowledge-base', route: '/knowledge-base/manage', role: 'admin', mobile: false },
  { uc: 'UC30', name: 'admin-generate-laporan', route: '/admin/reports', role: 'admin', mobile: false },
  { uc: 'UC31', name: 'admin-hr-checklist', route: '/admin/hr-checklists', role: 'admin', mobile: false },

  // --- Super Admin (desktop saja; akun admin@hrconnect.local = Super Admin) ---
  { uc: 'UC32', name: 'superadmin-user-role-permission', route: '/admin/roles-permissions', role: 'admin', mobile: false },
  { uc: 'UC33', name: 'superadmin-konfigurasi-sistem', route: '/admin/settings', role: 'admin', mobile: false },
  { uc: 'UC34', name: 'superadmin-activity-log', route: '/admin/activity-logs', role: 'admin', mobile: false },
  { uc: 'UC35', name: 'superadmin-masterdata-perusahaan', route: '/admin/companies', role: 'admin', mobile: false },
];

// ============================================================
// HELPERS
// ============================================================

function storageStateFor(role: Role): string | undefined {
  if (role === 'public') return undefined;
  return path.join(AUTH_DIR, `${role}.json`);
}

/**
 * Tulis status per-test utk MANIFEST failure-aware (scripts/generate-uc-manifest.mjs).
 * Folder .status/ per file PNG = anti-race antar worker paralel.
 * Dipanggil dari test body (catch-all) sehingga SEMUA jenis kegagalan tercatat:
 * assertion, goto gagal, screenshot timeout, dsb — tidak ada silent degradation.
 */
function writeUcStatus(baseName: string, ok: boolean, error?: string): void {
  const statusDir = path.join(OUT_DIR, '.status');
  fs.mkdirSync(statusDir, { recursive: true });
  fs.writeFileSync(
    path.join(statusDir, `${baseName}.json`),
    JSON.stringify({ ok, error: error ?? null, at: new Date().toISOString() }, null, 2),
  );
}

const ERROR_PATTERNS =
  /Server Error|500 Internal Server Error|403 Forbidden|404 Not Found|This action is unauthorized|Whoops, something went wrong/i;

async function captureAndVerify(page: Page, shot: UcShot, mode: 'desktop' | 'mobile'): Promise<void> {
  // Halaman admin berat (settings/import-export) butuh budget > default 30s:
  // navigasi + wait Livewire + fullPage screenshot kumulatif bisa ~60s.
  test.setTimeout(90_000);

  const consoleErrors: string[] = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') consoleErrors.push(msg.text().slice(0, 200));
  });
  page.on('pageerror', (err) => consoleErrors.push(`pageerror: ${String(err).slice(0, 200)}`));

  // domcontentloaded + tunggu Livewire siap — networkidle rapuh untuk halaman
  // yang punya polling/websocket persisten (Reverb, wire:poll) dan memakan
  // budget test sampai timeout (lesson UC24/UC33 run pertama).
  const t0 = Date.now();
  const log = (step: string) => console.log(`   [${shot.uc}-${shot.name}] +${((Date.now() - t0) / 1000).toFixed(1)}s ${step}`);

  const response = await page.goto(`${BASE_URL}${shot.route}`, {
    waitUntil: 'domcontentloaded',
    timeout: 30000,
  });
  log('goto done');

  await page
    .waitForFunction(() => (window as any).Livewire !== undefined, { timeout: 15000 })
    .catch(() => {});
  log('livewire ready');
  await page.waitForTimeout(3500);

  // Matikan Livewire polling supaya tidak re-render di tengah screenshot.
  // Promise.race: evaluate tidak punya timeout bawaan — kalau main thread
  // sibuk, jangan sampai menggantung test.
  await Promise.race([
    page.evaluate(() => {
      document.querySelectorAll('[wire\\:poll]').forEach((el) => el.removeAttribute('wire:poll'));
    }),
    new Promise((resolve) => setTimeout(resolve, 5000)),
  ]).catch(() => {});

  // Spinner wire:loading disembunyikan Livewire otomatis via class CSS —
  // cukup settle wait; jangan pakai waitForFunction tanpa options yang benar.
  await page.waitForTimeout(1500);

  // --- Verifikasi: halaman harus benar-benar sehat ---
  const status = response?.status() ?? 0;
  const finalUrl = page.url();
  const redirectedToLogin = finalUrl.includes('/login');
  const bodyText = await page
    .locator('body')
    .innerText({ timeout: 5000 })
    .catch(() => '');
  const looksLikeErrorPage = ERROR_PATTERNS.test(bodyText.slice(0, 3000));

  // Capture dulu (jadi artifact diagnosis kalau assert gagal)
  fs.mkdirSync(OUT_DIR, { recursive: true });
  const suffix = mode === 'mobile' ? '-mobile' : '';
  const filepath = path.join(OUT_DIR, `${shot.uc}-${shot.name}${suffix}.png`);
  log('screenshot start');
  await page.screenshot({ path: filepath, fullPage: true, timeout: 20000 });
  log('screenshot done');

  // Halaman login memang /login — cek redirect auth hanya untuk role ber-login
  if (shot.role !== 'public') {
    expect(redirectedToLogin, `${shot.route} redirect ke /login (auth/peran bermasalah)`).toBeFalsy();
  }
  expect(status, `${shot.route} HTTP status ${status}`).toBeLessThan(400);
  expect(looksLikeErrorPage, `${shot.route} merender error page`).toBeFalsy();
  expect(await page.locator('body').isVisible(), `${shot.route} body tidak visible`).toBeTruthy();

  if (consoleErrors.length > 0) {
    console.warn(`⚠ ${baseName}: ${consoleErrors.length} console error(s):`, consoleErrors.slice(0, 3));
  }
}

// ============================================================
// TEST GENERATION — dikelompokkan per role × mode
// ============================================================

const ROLES: Role[] = ['public', 'employee', 'manager', 'finance', 'admin'];

for (const role of ROLES) {
  for (const mode of ['desktop', 'mobile'] as const) {
    const group = SHOTS.filter((s) => s.role === role && (mode === 'desktop' || s.mobile));
    if (group.length === 0) continue;

    test.describe(`UC Screenshots: ${role.toUpperCase()} (${mode})`, () => {
      test.use({
        ...(mode === 'desktop'
          ? { viewport: { width: 1440, height: 900 } }
          : {
              viewport: { width: 390, height: 844 },
              userAgent:
                'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
              isMobile: true,
              hasTouch: true,
            }),
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        serviceWorkers: 'block',
        storageState: storageStateFor(role),
      });

      for (const shot of group) {
        test(`${shot.uc} ${shot.name} [${mode}]`, async ({ page }) => {
          const suffix = mode === 'mobile' ? '-mobile' : '';
          const baseName = `${shot.uc}-${shot.name}${suffix}`;
          try {
            await captureAndVerify(page, shot, mode);
            writeUcStatus(baseName, true);
          } catch (e) {
            writeUcStatus(baseName, false, e instanceof Error ? e.message.slice(0, 400) : String(e).slice(0, 400));
            throw e;
          }
        });
      }
    });
  }
}
