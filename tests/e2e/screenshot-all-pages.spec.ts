/**
 * HRConnect Comprehensive Screenshot Test
 *
 * Takes screenshots of ALL pages across ALL roles for documentation purposes.
 * Desktop: 1440x900, Mobile: 390x844
 *
 * Screenshots saved to: screenshots/{role}/desktop/ and screenshots/{role}/mobile/
 */
import { expect, test, type Page, type BrowserContext } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const BASE_URL = process.env.APP_URL || 'http://localhost:8000';

// ============================================================
// PAGE DEFINITIONS PER ROLE
// ============================================================

interface PageDefinition {
  /** Route path */
  route: string;
  /** Descriptive filename (without extension) */
  filename: string;
  /** Human-readable feature/use case description */
  description: string;
  /** Required role */
  role: 'employee' | 'manager' | 'finance' | 'admin' | 'superadmin';
  /** HTTP method to access (GET = normal page load, POST = form submit) */
  method?: 'GET' | 'POST';
  /** Optional: wait for specific selector before screenshot */
  waitFor?: string;
  /** Optional: additional steps before screenshot */
  setup?: (page: Page) => Promise<void>;
  /** Skip this page if condition is true */
  skip?: boolean;
  /** Skip reason */
  skipReason?: string;
}

// ============================================================
// EMPLOYEE PAGES
// ============================================================

const EMPLOYEE_PAGES: PageDefinition[] = [
  {
    route: '/home',
    filename: 'employee-dashboard',
    description: 'Dashboard employee - ringkasan absensi, cuti, notifikasi',
    role: 'employee',
  },
  {
    route: '/scan',
    filename: 'employee-presensi',
    description: 'Halaman presensi/clock-in dengan face recognition & GPS',
    role: 'employee',
  },
  {
    route: '/attendance-history',
    filename: 'employee-riwayat-absensi',
    description: 'Riwayat absensi harian/bulanan',
    role: 'employee',
  },
  {
    route: '/apply-leave',
    filename: 'employee-form-cuti',
    description: 'Form pengajuan cuti',
    role: 'employee',
  },
  {
    route: '/attendance-corrections',
    filename: 'employee-koreksi-presensi',
    description: 'Pengajuan koreksi presensi',
    role: 'employee',
  },
  {
    route: '/my-schedule',
    filename: 'employee-jadwal',
    description: 'Jadwal kerja mingguan/bulanan',
    role: 'employee',
  },
  {
    route: '/overtime',
    filename: 'employee-lembur',
    description: 'Daftar pengajuan lembur',
    role: 'employee',
  },
  {
    route: '/reimbursement',
    filename: 'employee-reimbursement',
    description: 'Daftar pengajuan reimbursement',
    role: 'employee',
  },
  {
    route: '/shift-swap-requests',
    filename: 'employee-shift-swap',
    description: 'Pengajuan tukar shift',
    role: 'employee',
  },
  {
    route: '/wfh-requests',
    filename: 'employee-wfh',
    description: 'Pengajuan work from home',
    role: 'employee',
  },
  {
    route: '/document-requests',
    filename: 'employee-dokumen',
    description: 'Pengajuan dokumen (SK, surat keterangan)',
    role: 'employee',
  },
  {
    route: '/hr-tasks',
    filename: 'employee-hr-tasks',
    description: 'Task HR checklist untuk employee',
    role: 'employee',
  },
  {
    route: '/my-tasks',
    filename: 'employee-tasks',
    description: 'Daftar tasks/my tasks',
    role: 'employee',
  },
  {
    route: '/collaboration',
    filename: 'employee-kolaborasi',
    description: 'Halaman kolaborasi tim',
    role: 'employee',
  },
  {
    route: '/forms',
    filename: 'employee-forms',
    description: 'Custom forms',
    role: 'employee',
  },
  {
    route: '/approvals',
    filename: 'employee-approval-pending',
    description: 'Approval pending untuk manager/subordinate',
    role: 'employee',
    skipReason: 'Hanya untuk role manager/HR',
    skip: true,
  },
  {
    route: '/my-kasbon',
    filename: 'employee-kasbon',
    description: 'Riwayat kasbon/cash advance saya',
    role: 'employee',
  },
  {
    route: '/team-kasbon',
    filename: 'employee-team-kasbon',
    description: 'Kasbon tim untuk approval',
    role: 'employee',
    skipReason: 'Hanya untuk role manager',
    skip: true,
  },
  {
    route: '/face-enrollment',
    filename: 'employee-face-enrollment',
    description: 'Enrollment wajah untuk face recognition',
    role: 'employee',
  },
  {
    route: '/my-assets',
    filename: 'employee-aset',
    description: 'Daftar aset yang dipinjam',
    role: 'employee',
  },
  {
    route: '/my-performance',
    filename: 'employee-performance',
    description: 'Penilaian kinerja/appraisal',
    role: 'employee',
  },
  {
    route: '/payroll',
    filename: 'employee-payslip',
    description: 'Daftar payslip/gaji bulanan',
    role: 'employee',
  },
  {
    route: '/knowledge-base',
    filename: 'employee-kb-index',
    description: 'Knowledge Base - daftar artikel',
    role: 'employee',
  },
  {
    route: '/knowledge-base/chat',
    filename: 'employee-kb-chat',
    description: 'KB Chat - AI RAG chatbot',
    role: 'employee',
  },
  {
    route: '/notifications',
    filename: 'employee-notifikasi',
    description: 'Daftar notifikasi',
    role: 'employee',
  },
  {
    route: '/user/profile',
    filename: 'employee-profil',
    description: 'Profil user - data diri',
    role: 'employee',
  },
];

// ============================================================
// MANAGER PAGES (additional to employee)
// ============================================================

const MANAGER_PAGES: PageDefinition[] = [
  {
    route: '/approvals',
    filename: 'manager-approval-pending',
    description: 'Approval pending - daftar pengajuan subordinate',
    role: 'manager',
  },
  {
    route: '/approvals/history',
    filename: 'manager-approval-history',
    description: 'Riwayat approval yang sudah diproses',
    role: 'manager',
  },
  {
    route: '/team-kasbon',
    filename: 'manager-team-kasbon',
    description: 'Approval kasbon tim',
    role: 'manager',
  },
  {
    route: '/admin/inbox',
    filename: 'manager-inbox',
    description: 'Manager inbox - pesan masuk',
    role: 'manager',
  },
  {
    route: '/admin/reports',
    filename: 'manager-reports',
    description: 'Laporan untuk manager',
    role: 'manager',
  },
];

// ============================================================
// FINANCE PAGES (additional to employee)
// ============================================================

const FINANCE_PAGES: PageDefinition[] = [
  {
    route: '/admin/payrolls',
    filename: 'finance-payroll-list',
    description: 'Daftar payroll bulanan',
    role: 'finance',
  },
  {
    route: '/admin/payrolls/settings',
    filename: 'finance-payroll-settings',
    description: 'Pengaturan payroll (PPh21, komponen gaji)',
    role: 'finance',
  },
  {
    route: '/admin/reimbursements',
    filename: 'finance-reimbursement-list',
    description: 'Daftar reimbursement untuk review',
    role: 'finance',
  },
  {
    route: '/admin/manage-kasbon',
    filename: 'finance-kasbon',
    description: 'Manajemen kasbon/cash advance',
    role: 'finance',
  },
  {
    route: '/admin/reports',
    filename: 'finance-reports',
    description: 'Laporan keuangan/payroll',
    role: 'finance',
  },
];

// ============================================================
// ADMIN/HR PAGES (additional to employee)
// ============================================================

const ADMIN_PAGES: PageDefinition[] = [
  {
    route: '/admin/dashboard',
    filename: 'admin-dashboard',
    description: 'Admin dashboard - overview karyawan, absensi, cuti',
    role: 'admin',
  },
  {
    route: '/admin/employees',
    filename: 'admin-data-karyawan',
    description: 'Daftar semua karyawan (master data)',
    role: 'admin',
  },
  {
    route: '/admin/employees/create',
    filename: 'admin-form-tambah-karyawan',
    description: 'Form tambah karyawan baru',
    role: 'admin',
  },
  {
    route: '/admin/attendances',
    filename: 'admin-absensi',
    description: 'Daftar absensi semua karyawan',
    role: 'admin',
  },
  {
    route: '/admin/attendances/report',
    filename: 'admin-report-absensi',
    description: 'Laporan absensi',
    role: 'admin',
  },
  {
    route: '/admin/leaves',
    filename: 'admin-cuti',
    description: 'Daftar pengajuan cuti untuk approval',
    role: 'admin',
  },
  {
    route: '/admin/attendance-corrections',
    filename: 'admin-koreksi-presensi',
    description: 'Daftar koreksi presensi untuk approval',
    role: 'admin',
  },
  {
    route: '/admin/overtime',
    filename: 'admin-lembur',
    description: 'Daftar lembur untuk approval',
    role: 'admin',
  },
  {
    route: '/admin/shift-swaps',
    filename: 'admin-shift-swap',
    description: 'Daftar pengajuan tukar shift',
    role: 'admin',
  },
  {
    route: '/admin/schedules',
    filename: 'admin-jadwal',
    description: 'Manajemen jadwal kerja',
    role: 'admin',
  },
  {
    route: '/admin/payrolls',
    filename: 'admin-payroll',
    description: 'Daftar payroll bulanan',
    role: 'admin',
  },
  {
    route: '/admin/payrolls/settings',
    filename: 'admin-payroll-settings',
    description: 'Pengaturan payroll (komponen gaji, PPh21)',
    role: 'admin',
  },
  {
    route: '/admin/reimbursements',
    filename: 'admin-reimbursement',
    description: 'Daftar reimbursement untuk review',
    role: 'admin',
  },
  {
    route: '/admin/document-requests',
    filename: 'admin-dokumen',
    description: 'Daftar pengajuan dokumen karyawan',
    role: 'admin',
  },
  {
    route: '/admin/document-templates',
    filename: 'admin-template-dokumen',
    description: 'Template dokumen',
    role: 'admin',
  },
  {
    route: '/admin/hr-checklists',
    filename: 'admin-hr-checklist',
    description: 'HR checklist tasks',
    role: 'admin',
  },
  {
    route: '/admin/announcements',
    filename: 'admin-pengumuman',
    description: 'Manajemen pengumuman',
    role: 'admin',
  },
  {
    route: '/admin/holidays',
    filename: 'admin-libur',
    description: 'Kalender libur nasional/cuti bersama',
    role: 'admin',
  },
  {
    route: '/admin/notifications',
    filename: 'admin-notifikasi',
    description: 'Notifikasi admin',
    role: 'admin',
  },
  {
    route: '/admin/collaboration',
    filename: 'admin-kolaborasi',
    description: 'Kolaborasi tim admin',
    role: 'admin',
  },
  {
    route: '/admin/operations',
    filename: 'admin-operations',
    description: 'Operational management',
    role: 'admin',
  },
  {
    route: '/admin/analytics',
    filename: 'admin-analytics',
    description: 'Analytics dashboard',
    role: 'admin',
  },
  {
    route: '/admin/import-export/users',
    filename: 'admin-import-export',
    description: 'Import/Export data karyawan',
    role: 'admin',
  },
  {
    route: '/admin/inbox',
    filename: 'admin-inbox',
    description: 'Inbox admin/HR',
    role: 'admin',
  },
  {
    route: '/admin/reports',
    filename: 'admin-reports',
    description: 'Report center - laporan komprehensif',
    role: 'admin',
  },
];

// ============================================================
// SUPER ADMIN PAGES (additional to admin)
// ============================================================

const SUPERADMIN_PAGES: PageDefinition[] = [
  {
    route: '/admin/roles-permissions',
    filename: 'superadmin-role-permission',
    description: 'Manajemen role & permission',
    role: 'superadmin',
  },
  {
    route: '/admin/settings',
    filename: 'superadmin-settings',
    description: 'Pengaturan sistem (app name, logo, etc)',
    role: 'superadmin',
  },
  {
    route: '/admin/settings/kpi',
    filename: 'superadmin-kpi',
    description: 'Pengaturan KPI',
    role: 'superadmin',
  },
  {
    route: '/admin/companies',
    filename: 'superadmin-companies',
    description: 'Manajemen data perusahaan',
    role: 'superadmin',
  },
  {
    route: '/admin/user-sessions',
    filename: 'superadmin-sessions',
    description: 'Active user sessions',
    role: 'superadmin',
  },
  {
    route: '/admin/system-maintenance',
    filename: 'superadmin-maintenance',
    description: 'System maintenance tools',
    role: 'superadmin',
  },
  {
    route: '/admin/activity-logs',
    filename: 'superadmin-activity-logs',
    description: 'Audit trail / activity logs',
    role: 'superadmin',
  },
  {
    route: '/admin/appraisals',
    filename: 'superadmin-appraisals',
    description: 'Manajemen appraisal/penilaian kinerja',
    role: 'superadmin',
  },
  {
    route: '/admin/assets',
    filename: 'superadmin-assets',
    description: 'Manajemen aset perusahaan',
    role: 'superadmin',
  },
  {
    route: '/admin/custom-forms',
    filename: 'superadmin-custom-forms',
    description: 'Custom forms builder',
    role: 'superadmin',
  },
  {
    route: '/admin/masterdata/admin',
    filename: 'superadmin-masterdata-admin',
    description: 'Master data admin',
    role: 'superadmin',
  },
  {
    route: '/admin/masterdata/division',
    filename: 'superadmin-masterdata-divisi',
    description: 'Master data divisi',
    role: 'superadmin',
  },
  {
    route: '/admin/masterdata/job-title',
    filename: 'superadmin-masterdata-jabatan',
    description: 'Master data jabatan/job title',
    role: 'superadmin',
  },
  {
    route: '/admin/masterdata/education',
    filename: 'superadmin-masterdata-pendidikan',
    description: 'Master data pendidikan',
    role: 'superadmin',
  },
  {
    route: '/admin/masterdata/shift',
    filename: 'superadmin-masterdata-shift',
    description: 'Master data shift',
    role: 'superadmin',
  },
  {
    route: '/admin/masterdata/leave-types',
    filename: 'superadmin-masterdata-jenis-cuti',
    description: 'Master data jenis cuti',
    role: 'superadmin',
  },
  {
    route: '/admin/masterdata/leave-entitlements',
    filename: 'superadmin-masterdata-hak-cuti',
    description: 'Master data hak cuti karyawan',
    role: 'superadmin',
  },
  {
    route: '/admin/operational-health',
    filename: 'superadmin-operational-health',
    description: 'Operational health monitoring',
    role: 'superadmin',
  },
];

// ============================================================
// HELPER: Take screenshot in specified viewport
// ============================================================

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
    await page.screenshot({
      path: filepath,
      fullPage: true,
      timeout: 15000,
    });
    return { success: true };
  } catch (error: any) {
    return { success: false, error: error.message };
  }
}

// ============================================================
// HELPER: Navigate and wait for page to be ready
// ============================================================

async function navigateToPage(page: Page, route: string, waitFor?: string): Promise<number> {
  const response = await page.goto(`${BASE_URL}${route}`, {
    waitUntil: 'domcontentloaded',
    timeout: 30000,
  });

  // Wait for Livewire to finish loading
  await page.waitForTimeout(2000);

  // Wait for specific element if provided
  if (waitFor) {
    try {
      await page.waitForSelector(waitFor, { timeout: 5000 });
    } catch {
      // Element not found, continue anyway
    }
  }

  // Wait for any loading spinners to disappear
  try {
    await page.waitForFunction(() => {
      const spinners = document.querySelectorAll('[wire\\:loading]');
      return Array.from(spinners).every((el) => el.getAttribute('wire\\:loading') === null || (el as HTMLElement).style.display === 'none');
    }, { timeout: 5000 });
  } catch {
    // Continue anyway
  }

  return response?.status() || 0;
}

// ============================================================
// MAIN TEST: Screenshot all pages for all roles
// ============================================================

const allRoles = ['employee', 'manager', 'finance', 'admin', 'superadmin'] as const;

for (const role of allRoles) {
  test.describe(`Screenshots: ${role.toUpperCase()}`, () => {
    // Combine pages for this role
    let pages: PageDefinition[] = [];

    switch (role) {
      case 'employee':
        pages = EMPLOYEE_PAGES;
        break;
      case 'manager':
        pages = [...EMPLOYEE_PAGES.filter((p) => p.role === 'employee'), ...MANAGER_PAGES];
        break;
      case 'finance':
        pages = [...EMPLOYEE_PAGES.filter((p) => p.role === 'employee'), ...FINANCE_PAGES];
        break;
      case 'admin':
        pages = [...EMPLOYEE_PAGES.filter((p) => p.role === 'employee'), ...ADMIN_PAGES];
        break;
      case 'superadmin':
        pages = [
          ...EMPLOYEE_PAGES.filter((p) => p.role === 'employee'),
          ...ADMIN_PAGES,
          ...SUPERADMIN_PAGES,
        ];
        break;
    }

    // Desktop screenshots
    test.describe('Desktop (1440x900)', () => {
      test.use({
        viewport: { width: 1440, height: 900 },
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        serviceWorkers: 'block',
      });

      for (const pageDef of pages) {
        const testFn = async ({ page }: { page: Page }) => {
          if (pageDef.skip) {
            test.skip(true, pageDef.skipReason || 'Skipped');
            return;
          }

          const status = await navigateToPage(page, pageDef.route, pageDef.waitFor);

          // Take setup actions if defined
          if (pageDef.setup) {
            await pageDef.setup(page);
          }

          const result = await takeScreenshot(page, role, pageDef.filename, 'desktop');

          // Verify page loaded successfully
          expect(status, `Page ${pageDef.route} returned status ${status}`).toBeLessThan(500);
          expect(result.success, `Screenshot failed: ${result.error}`).toBeTruthy();
          expect(page.locator('body')).toBeVisible();
        };

        test(`${pageDef.filename}: ${pageDef.description}`, testFn);
      }
    });

    // Mobile screenshots
    test.describe('Mobile (390x844)', () => {
      test.use({
        viewport: { width: 390, height: 844 },
        userAgent:
          'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        serviceWorkers: 'block',
        isMobile: true,
        hasTouch: true,
      });

      for (const pageDef of pages) {
        const testFn = async ({ page }: { page: Page }) => {
          if (pageDef.skip) {
            test.skip(true, pageDef.skipReason || 'Skipped');
            return;
          }

          const status = await navigateToPage(page, pageDef.route, pageDef.waitFor);

          // Take setup actions if defined
          if (pageDef.setup) {
            await pageDef.setup(page);
          }

          const result = await takeScreenshot(page, role, `${pageDef.filename}-mobile`, 'mobile');

          // Verify page loaded successfully
          expect(status, `Page ${pageDef.route} returned status ${status}`).toBeLessThan(500);
          expect(result.success, `Screenshot failed: ${result.error}`).toBeTruthy();
          expect(page.locator('body')).toBeVisible();
        };

        test(`mobile ${pageDef.filename}: ${pageDef.description}`, testFn);
      }
    });
  });
}
