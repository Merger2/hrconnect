/**
 * HRConnect Comprehensive Screenshot Test
 *
 * Takes screenshots of ALL pages across ALL roles for documentation.
 * Desktop: 1440x900, Mobile: 390x844
 *
 * Screenshots saved to: screenshots/{role}/desktop/ and screenshots/{role}/mobile/
 *
 * IMPORTANT: Manager/Finance use employee routes (same UI, different auth).
 * Admin/Superadmin use /admin/* routes.
 */
import { expect, test, type Page } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const BASE_URL = process.env.APP_URL || 'http://localhost:8000';
const AUTH_DIR = path.join(__dirname, '.auth');

// ============================================================
// PAGE DEFINITIONS PER ROLE
// ============================================================

interface PageDefinition {
  route: string;
  filename: string;
  description: string;
  waitFor?: string;
  skip?: boolean;
  skipReason?: string;
}

// ============================================================
// LOGIN PAGE (no auth needed)
// ============================================================

const LOGIN_PAGES: PageDefinition[] = [
  {
    route: '/login',
    filename: 'login-page',
    description: 'Halaman login HRConnect',
  },
  {
    route: '/forgot-password',
    filename: 'forgot-password-page',
    description: 'Halaman lupa password',
  },
];

// ============================================================
// EMPLOYEE PAGES (also used by Manager & Finance)
// ============================================================

const EMPLOYEE_PAGES: PageDefinition[] = [
  {
    route: '/home',
    filename: 'employee-dashboard',
    description: 'Dashboard employee - ringkasan absensi, cuti, notifikasi',
  },
  {
    route: '/scan',
    filename: 'employee-presensi',
    description: 'Halaman presensi/clock-in dengan face recognition & GPS',
  },
  {
    route: '/attendance-history',
    filename: 'employee-riwayat-absensi',
    description: 'Riwayat absensi harian/bulanan',
  },
  {
    route: '/apply-leave',
    filename: 'employee-form-cuti',
    description: 'Form pengajuan cuti',
  },
  {
    route: '/attendance-corrections',
    filename: 'employee-koreksi-presensi',
    description: 'Pengajuan koreksi presensi',
  },
  {
    route: '/my-schedule',
    filename: 'employee-jadwal',
    description: 'Jadwal kerja mingguan/bulanan',
  },
  {
    route: '/overtime',
    filename: 'employee-lembur',
    description: 'Daftar pengajuan lembur',
  },
  {
    route: '/reimbursement',
    filename: 'employee-reimbursement',
    description: 'Daftar pengajuan reimbursement',
  },
  {
    route: '/shift-swap-requests',
    filename: 'employee-shift-swap',
    description: 'Pengajuan tukar shift',
  },
  {
    route: '/wfh-requests',
    filename: 'employee-wfh',
    description: 'Pengajuan work from home',
  },
  {
    route: '/document-requests',
    filename: 'employee-dokumen',
    description: 'Pengajuan dokumen (SK, surat keterangan)',
  },
  {
    route: '/hr-tasks',
    filename: 'employee-hr-tasks',
    description: 'Task HR checklist untuk employee',
  },
  {
    route: '/my-tasks',
    filename: 'employee-tasks',
    description: 'Daftar tasks/my tasks',
  },
  {
    route: '/collaboration',
    filename: 'employee-kolaborasi',
    description: 'Halaman kolaborasi tim',
  },
  {
    route: '/forms',
    filename: 'employee-forms',
    description: 'Custom forms',
  },
  {
    route: '/my-kasbon',
    filename: 'employee-kasbon',
    description: 'Riwayat kasbon/cash advance saya',
  },
  {
    route: '/face-enrollment',
    filename: 'employee-face-enrollment',
    description: 'Enrollment wajah untuk face recognition',
  },
  {
    route: '/my-assets',
    filename: 'employee-aset',
    description: 'Daftar aset yang dipinjam',
  },
  {
    route: '/my-performance',
    filename: 'employee-performance',
    description: 'Penilaian kinerja/appraisal',
  },
  {
    route: '/payroll',
    filename: 'employee-payslip',
    description: 'Daftar payslip/gaji bulanan',
  },
  {
    route: '/knowledge-base',
    filename: 'employee-kb-index',
    description: 'Knowledge Base - daftar artikel',
  },
  {
    route: '/knowledge-base/chat',
    filename: 'employee-kb-chat',
    description: 'KB Chat - AI RAG chatbot',
  },
  {
    route: '/notifications',
    filename: 'employee-notifikasi',
    description: 'Daftar notifikasi',
  },
  {
    route: '/user/profile',
    filename: 'employee-profil',
    description: 'Profil user - data diri',
  },
];

// ============================================================
// MANAGER EXTRA PAGES (on top of employee pages)
// ============================================================

const MANAGER_EXTRA_PAGES: PageDefinition[] = [
  {
    route: '/approvals',
    filename: 'manager-approval-pending',
    description: 'Approval pending - daftar pengajuan subordinate',
  },
  {
    route: '/approvals/history',
    filename: 'manager-approval-history',
    description: 'Riwayat approval yang sudah diproses',
  },
  {
    route: '/team-kasbon',
    filename: 'manager-team-kasbon',
    description: 'Approval kasbon tim',
  },
];

// ============================================================
// ADMIN PAGES (/admin/* prefix, need admin auth)
// ============================================================

const ADMIN_PAGES: PageDefinition[] = [
  {
    route: '/admin/dashboard',
    filename: 'admin-dashboard',
    description: 'Admin dashboard - overview karyawan, absensi, cuti',
  },
  {
    route: '/admin/employees',
    filename: 'admin-data-karyawan',
    description: 'Daftar semua karyawan (master data)',
  },
  {
    route: '/admin/employees/create',
    filename: 'admin-form-tambah-karyawan',
    description: 'Form tambah karyawan baru',
  },
  {
    route: '/admin/attendances',
    filename: 'admin-absensi',
    description: 'Daftar absensi semua karyawan',
  },
  {
    route: '/admin/attendances/report',
    filename: 'admin-report-absensi',
    description: 'Laporan absensi',
  },
  {
    route: '/admin/leaves',
    filename: 'admin-cuti',
    description: 'Daftar pengajuan cuti untuk approval',
  },
  {
    route: '/admin/attendance-corrections',
    filename: 'admin-koreksi-presensi',
    description: 'Daftar koreksi presensi untuk approval',
  },
  {
    route: '/admin/overtime',
    filename: 'admin-lembur',
    description: 'Daftar lembur untuk approval',
  },
  {
    route: '/admin/shift-swaps',
    filename: 'admin-shift-swap',
    description: 'Daftar pengajuan tukar shift',
  },
  {
    route: '/admin/schedules',
    filename: 'admin-jadwal',
    description: 'Manajemen jadwal kerja',
  },
  {
    route: '/admin/payrolls',
    filename: 'admin-payroll',
    description: 'Daftar payroll bulanan',
  },
  {
    route: '/admin/payrolls/settings',
    filename: 'admin-payroll-settings',
    description: 'Pengaturan payroll (komponen gaji, PPh21)',
  },
  {
    route: '/admin/reimbursements',
    filename: 'admin-reimbursement',
    description: 'Daftar reimbursement untuk review',
  },
  {
    route: '/admin/document-requests',
    filename: 'admin-dokumen',
    description: 'Daftar pengajuan dokumen karyawan',
  },
  {
    route: '/admin/document-templates',
    filename: 'admin-template-dokumen',
    description: 'Template dokumen',
  },
  {
    route: '/admin/hr-checklists',
    filename: 'admin-hr-checklist',
    description: 'HR checklist tasks',
  },
  {
    route: '/admin/announcements',
    filename: 'admin-pengumuman',
    description: 'Manajemen pengumuman',
  },
  {
    route: '/admin/holidays',
    filename: 'admin-libur',
    description: 'Kalender libur nasional/cuti bersama',
  },
  {
    route: '/admin/notifications',
    filename: 'admin-notifikasi',
    description: 'Notifikasi admin',
  },
  {
    route: '/admin/collaboration',
    filename: 'admin-kolaborasi',
    description: 'Kolaborasi tim admin',
  },
  {
    route: '/admin/operations',
    filename: 'admin-operations',
    description: 'Operational management',
  },
  {
    route: '/admin/analytics',
    filename: 'admin-analytics',
    description: 'Analytics dashboard',
  },
  {
    route: '/admin/import-export/users',
    filename: 'admin-import-export',
    description: 'Import/Export data karyawan',
  },
  {
    route: '/admin/inbox',
    filename: 'admin-inbox',
    description: 'Inbox admin/HR',
  },
  {
    route: '/admin/reports',
    filename: 'admin-reports',
    description: 'Report center - laporan komprehensif',
  },
  {
    route: '/admin/manage-kasbon',
    filename: 'admin-kasbon',
    description: 'Manajemen kasbon/cash advance',
  },
];

// ============================================================
// SUPER ADMIN EXTRA PAGES (on top of admin pages)
// ============================================================

const SUPERADMIN_EXTRA_PAGES: PageDefinition[] = [
  {
    route: '/admin/roles-permissions',
    filename: 'superadmin-role-permission',
    description: 'Manajemen role & permission',
  },
  {
    route: '/admin/settings',
    filename: 'superadmin-settings',
    description: 'Pengaturan sistem (app name, logo, etc)',
  },
  {
    route: '/admin/settings/kpi',
    filename: 'superadmin-kpi',
    description: 'Pengaturan KPI',
  },
  {
    route: '/admin/companies',
    filename: 'superadmin-companies',
    description: 'Manajemen data perusahaan',
  },
  {
    route: '/admin/user-sessions',
    filename: 'superadmin-sessions',
    description: 'Active user sessions',
  },
  {
    route: '/admin/system-maintenance',
    filename: 'superadmin-maintenance',
    description: 'System maintenance tools',
  },
  {
    route: '/admin/activity-logs',
    filename: 'superadmin-activity-logs',
    description: 'Audit trail / activity logs',
  },
  {
    route: '/admin/appraisals',
    filename: 'superadmin-appraisals',
    description: 'Manajemen appraisal/penilaian kinerja',
  },
  {
    route: '/admin/assets',
    filename: 'superadmin-assets',
    description: 'Manajemen aset perusahaan',
  },
  {
    route: '/admin/custom-forms',
    filename: 'superadmin-custom-forms',
    description: 'Custom forms builder',
  },
  {
    route: '/admin/masterdata/admin',
    filename: 'superadmin-masterdata-admin',
    description: 'Master data admin',
  },
  {
    route: '/admin/masterdata/division',
    filename: 'superadmin-masterdata-divisi',
    description: 'Master data divisi',
  },
  {
    route: '/admin/masterdata/job-title',
    filename: 'superadmin-masterdata-jabatan',
    description: 'Master data jabatan/job title',
  },
  {
    route: '/admin/masterdata/education',
    filename: 'superadmin-masterdata-pendidikan',
    description: 'Master data pendidikan',
  },
  {
    route: '/admin/masterdata/shift',
    filename: 'superadmin-masterdata-shift',
    description: 'Master data shift',
  },
  {
    route: '/admin/masterdata/leave-types',
    filename: 'superadmin-masterdata-jenis-cuti',
    description: 'Master data jenis cuti',
  },
  {
    route: '/admin/masterdata/leave-entitlements',
    filename: 'superadmin-masterdata-hak-cuti',
    description: 'Master data hak cuti karyawan',
  },
  {
    route: '/admin/operational-health',
    filename: 'superadmin-operational-health',
    description: 'Operational health monitoring',
  },
];

// ============================================================
// HELPERS
// ============================================================

function getAuthFile(role: string): string {
  switch (role) {
    case 'employee':
    case 'manager':
    case 'finance':
      return 'employee.json';
    case 'admin':
    case 'superadmin':
      return 'admin.json';
    default:
      return 'employee.json';
  }
}

function loadStorageState(role: string): object {
  const file = path.join(AUTH_DIR, getAuthFile(role));
  if (fs.existsSync(file)) {
    return JSON.parse(fs.readFileSync(file, 'utf-8'));
  }
  return { cookies: [], origins: [] };
}

async function takeScreenshot(
  page: Page,
  role: string,
  filename: string,
  viewport: 'desktop' | 'mobile',
): Promise<{ success: boolean; error?: string }> {
  const dir = path.join(process.cwd(), 'screenshots', role, viewport);
  fs.mkdirSync(dir, { recursive: true });
  const filepath = path.join(dir, `${filename}.png`);

  try {
    await page.screenshot({ path: filepath, fullPage: true, timeout: 15000 });
    return { success: true };
  } catch (error: any) {
    return { success: false, error: error.message };
  }
}

async function navigateToPage(page: Page, route: string, storageState?: object): Promise<number> {
  if (storageState) {
    // Apply cookies from storage state
    const cookies = (storageState as any).cookies || [];
    if (cookies.length > 0) {
      await page.context().addCookies(cookies);
    }
  }

  const response = await page.goto(`${BASE_URL}${route}`, {
    waitUntil: 'domcontentloaded',
    timeout: 30000,
  });

  // Wait for Livewire to finish loading
  await page.waitForTimeout(2000);

  // Wait for loading spinners to disappear
  try {
    await page.waitForFunction(
      () => {
        const spinners = document.querySelectorAll('[wire\\:loading]');
        return Array.from(spinners).every(
          (el) => !el.getAttribute('wire\\:loading') || (el as HTMLElement).style.display === 'none',
        );
      },
      { timeout: 5000 },
    );
  } catch {
    // Continue anyway
  }

  return response?.status() || 0;
}

// ============================================================
// LOGIN PAGE TEST (no auth needed)
// ============================================================

test.describe('Screenshots: LOGIN PAGE', () => {
  test.use({
    viewport: { width: 1440, height: 900 },
    permissions: ['camera', 'geolocation'],
    serviceWorkers: 'block',
  });

  for (const pageDef of LOGIN_PAGES) {
    test(`desktop ${pageDef.filename}: ${pageDef.description}`, async ({ page }) => {
      const status = await navigateToPage(page, pageDef.route);
      const result = await takeScreenshot(page, 'public', pageDef.filename, 'desktop');
      expect(status, `Page ${pageDef.route} returned status ${status}`).toBeLessThan(500);
      expect(result.success, `Screenshot failed: ${result.error}`).toBeTruthy();
    });
  }
});

test.describe('Screenshots: LOGIN PAGE (Mobile)', () => {
  test.use({
    viewport: { width: 390, height: 844 },
    userAgent:
      'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
    permissions: ['camera', 'geolocation'],
    isMobile: true,
    hasTouch: true,
    serviceWorkers: 'block',
  });

  for (const pageDef of LOGIN_PAGES) {
    test(`mobile ${pageDef.filename}: ${pageDef.description}`, async ({ page }) => {
      const status = await navigateToPage(page, pageDef.route);
      const result = await takeScreenshot(page, 'public', `${pageDef.filename}-mobile`, 'mobile');
      expect(status, `Page ${pageDef.route} returned status ${status}`).toBeLessThan(500);
      expect(result.success, `Screenshot failed: ${result.error}`).toBeTruthy();
    });
  }
});

// ============================================================
// ROLE-BASED SCREENSHOT TESTS
// ============================================================

type RoleConfig = {
  name: string;
  employeePages: PageDefinition[];
  extraPages: PageDefinition[];
};

const ROLE_CONFIGS: RoleConfig[] = [
  {
    name: 'employee',
    employeePages: EMPLOYEE_PAGES,
    extraPages: [],
  },
  {
    name: 'manager',
    employeePages: EMPLOYEE_PAGES,
    extraPages: MANAGER_EXTRA_PAGES,
  },
  {
    name: 'finance',
    employeePages: EMPLOYEE_PAGES,
    extraPages: [],
  },
  {
    name: 'admin',
    employeePages: [],
    extraPages: ADMIN_PAGES,
  },
  {
    name: 'superadmin',
    employeePages: [],
    extraPages: [...ADMIN_PAGES, ...SUPERADMIN_EXTRA_PAGES],
  },
];

for (const roleConfig of ROLE_CONFIGS) {
  const { name: role, employeePages, extraPages } = roleConfig;
  const allPages = [...employeePages, ...extraPages];

  // Desktop
  test.describe(`Screenshots: ${role.toUpperCase()} (Desktop)`, () => {
    test.use({
      viewport: { width: 1440, height: 900 },
      permissions: ['camera', 'geolocation'],
      geolocation: { latitude: -6.2088, longitude: 106.8456 },
      serviceWorkers: 'block',
    });

    for (const pageDef of allPages) {
      if (pageDef.skip) continue;

      test(`${pageDef.filename}: ${pageDef.description}`, async ({ page }) => {
        const storageState = loadStorageState(role);
        const status = await navigateToPage(page, pageDef.route, storageState);

        // If redirected to login, re-navigate with cookies
        if (page.url().includes('/login')) {
          await page.context().addCookies((storageState as any).cookies || []);
          await page.goto(`${BASE_URL}${pageDef.route}`, {
            waitUntil: 'domcontentloaded',
            timeout: 30000,
          });
          await page.waitForTimeout(2000);
        }

        const result = await takeScreenshot(page, role, pageDef.filename, 'desktop');
        expect(status, `Page ${pageDef.route} returned status ${status}`).toBeLessThan(500);
        expect(result.success, `Screenshot failed: ${result.error}`).toBeTruthy();
        expect(page.locator('body')).toBeVisible();
      });
    }
  });

  // Mobile
  test.describe(`Screenshots: ${role.toUpperCase()} (Mobile)`, () => {
    test.use({
      viewport: { width: 390, height: 844 },
      userAgent:
        'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
      permissions: ['camera', 'geolocation'],
      geolocation: { latitude: -6.2088, longitude: 106.8456 },
      isMobile: true,
      hasTouch: true,
      serviceWorkers: 'block',
    });

    for (const pageDef of allPages) {
      if (pageDef.skip) continue;

      test(`mobile ${pageDef.filename}: ${pageDef.description}`, async ({ page }) => {
        const storageState = loadStorageState(role);
        const status = await navigateToPage(page, pageDef.route, storageState);

        // If redirected to login, re-navigate with cookies
        if (page.url().includes('/login')) {
          await page.context().addCookies((storageState as any).cookies || []);
          await page.goto(`${BASE_URL}${pageDef.route}`, {
            waitUntil: 'domcontentloaded',
            timeout: 30000,
          });
          await page.waitForTimeout(2000);
        }

        const result = await takeScreenshot(page, role, `${pageDef.filename}-mobile`, 'mobile');
        expect(status, `Page ${pageDef.route} returned status ${status}`).toBeLessThan(500);
        expect(result.success, `Screenshot failed: ${result.error}`).toBeTruthy();
        expect(page.locator('body')).toBeVisible();
      });
    }
  });
}
