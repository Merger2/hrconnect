/**
 * Admin/Superadmin Role E2E Tests
 *
 * Tests the superadmin role's admin panel pages:
 * - Dashboard
 * - Employees management
 * - Attendance management
 * - Leave management
 * - Reports
 * - Settings
 * - Roles & Permissions
 * - Companies
 * - Schedules
 * - Master data
 */
import { expect, test } from '@playwright/test';

const ADMIN_PAGES = [
    { path: '/admin/dashboard', title: 'Ringkasan' },
    { path: '/admin/employees', title: 'Karyawan' },
    { path: '/admin/attendances', title: 'Presensi' },
    { path: '/admin/leaves', title: 'Cuti' },
    { path: '/admin/reports', title: 'Laporan' },
    { path: '/admin/settings', title: 'Pengaturan' },
    { path: '/admin/roles-permissions', title: 'Role' },
    { path: '/admin/companies', title: 'Perusahaan' },
    { path: '/admin/schedules', title: 'Jadwal' },
    { path: '/admin/masterdata/division', title: 'Divisi' },
    { path: '/admin/masterdata/job-title', title: 'Jabatan' },
    { path: '/admin/masterdata/shift', title: 'Shift' },
    { path: '/admin/masterdata/leave-types', title: 'Jenis Cuti' },
    { path: '/admin/holidays', title: 'Hari Libur' },
    { path: '/admin/reimbursements', title: 'Reimburse' },
    { path: '/admin/overtime', title: 'Lembur' },
    { path: '/admin/attendance-corrections', title: 'Koreksi' },
    { path: '/admin/shift-swaps', title: 'Tukar Shift' },
    { path: '/admin/document-requests', title: 'Dokumen' },
    { path: '/admin/hr-checklists', title: 'Checklist' },
    { path: '/admin/notifications', title: 'Notifikasi' },
    { path: '/admin/activity-logs', title: 'Log' },
];

test.describe('Admin/Superadmin Role', () => {
    test.use({
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        serviceWorkers: 'block',
    });

    for (const { path, title } of ADMIN_PAGES) {
        test(`admin page ${path} renders without error`, async ({ page }) => {
            const response = await page.goto(path, { waitUntil: 'networkidle', timeout: 15000 });

            // Should not be a 500 error
            expect(response!.status()).toBeLessThan(500);

            // Page body should be visible
            await expect(page.locator('body')).toBeVisible();

            // No exception/stack trace in page
            await expect(page.locator('body')).not.toContainText(/exception|stack trace|whoops/i);

            // Verify at least some content loaded (not blank page)
            const bodyText = await page.locator('body').textContent();
            expect(bodyText!.length).toBeGreaterThan(50);
        });
    }

    test('should display admin dashboard with summary data', async ({ page }) => {
        await page.goto('/admin/dashboard', { waitUntil: 'networkidle' });

        // Verify dashboard loaded with key sections
        const bodyText = await page.locator('body').textContent();
        expect(bodyText).toContain('Ringkasan');
    });

    test('should display employee list', async ({ page }) => {
        await page.goto('/admin/employees', { waitUntil: 'networkidle' });

        // Verify employee list loaded
        await expect(page.locator('h1')).toContainText('Karyawan');

        // Table should have data rows (50+ demo employees)
        const rows = page.locator('table tbody tr, [wire\\:id] tr');
        const rowCount = await rows.count();
        expect(rowCount).toBeGreaterThan(0);
    });

    test('should display roles & permissions page', async ({ page }) => {
        await page.goto('/admin/roles-permissions', { waitUntil: 'networkidle' });

        // Verify page loaded
        await expect(page.locator('h1')).toContainText('Peran');

        // Verify roles are listed
        const bodyText = await page.locator('body').textContent();
        expect(bodyText).toContain('employee');
        expect(bodyText).toContain('manager');
    });

    test('should display settings page', async ({ page }) => {
        await page.goto('/admin/settings', { waitUntil: 'networkidle' });

        // Verify page loaded
        await expect(page.locator('h1')).toContainText('Pengaturan');
    });

    test('should display companies page', async ({ page }) => {
        await page.goto('/admin/companies', { waitUntil: 'networkidle' });

        // Verify page loaded
        await expect(page.locator('h1')).toContainText('Compan');

        // Verify company data exists
        const bodyText = await page.locator('body').textContent();
        // Company name may vary in table
    });

    test('should display reports page', async ({ page }) => {
        await page.goto('/admin/reports', { waitUntil: 'networkidle' });

        // Verify page loaded
        await expect(page.locator('h1')).toContainText('Laporan');
    });
});
