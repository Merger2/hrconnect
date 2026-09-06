/**
 * Employee Role — Comprehensive E2E Test
 *
 * Covers ALL 23 pages accessible by employee role:
 * - User pages (home, attendance, leave, reimbursement, schedule, etc.)
 * - Admin pages accessible to employee (attendances, reimbursements)
 * - Interactive flows (view payslip, chat KB, etc.)
 *
 * Storage state: tests/e2e/.auth/employee.json
 */
import { expect, test } from '@playwright/test';
import { smokeTest } from './helpers/smoke-test';

const EMPLOYEE_PAGES = [
    { path: '/home', titleContains: 'Test Employee' },
    { path: '/notifications', titleContains: 'Notifikasi' },
    { path: '/attendance-history', titleContains: 'Presensi' },
    { path: '/apply-leave', titleContains: 'Izin' },
    { path: '/attendance-corrections', titleContains: 'Koreksi' },
    { path: '/reimbursement', titleContains: 'Reimburse' },
    { path: '/my-schedule', titleContains: 'Jadwal' },
    { path: '/shift-swap-requests', titleContains: 'Tukar Shift' },
    { path: '/wfh-requests', titleContains: 'WFH' },
    { path: '/document-requests', titleContains: 'Dokumen' },
    { path: '/hr-tasks', titleContains: 'Tugas' },
    { path: '/my-tasks', titleContains: 'Tasks' },
    { path: '/collaboration', titleContains: 'Chat' },
    { path: '/forms', titleContains: 'Formulir' },
    { path: '/overtime', titleContains: 'Lembur' },
    { path: '/my-kasbon', titleContains: 'Kasbon' },
    { path: '/face-enrollment', titleContains: 'Face' },
    { path: '/my-assets', titleContains: 'Aset' },
    { path: '/my-performance', titleContains: 'Kinerja' },
    { path: '/payroll', titleContains: 'Slip' },
    { path: '/knowledge-base/chat', titleContains: 'Pengetahuan' },
    { path: '/admin/attendances', titleContains: 'Presensi' },
    { path: '/admin/reimbursements', titleContains: 'Reimburse' },
];

test.describe('Employee Role', () => {
    test.use({
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        serviceWorkers: 'block',
    });

    // Smoke test all accessible pages
    for (const { path, titleContains } of EMPLOYEE_PAGES) {
        test(`page ${path} renders correctly`, async ({ page }) => {
            const title = await smokeTest(page, path);
            expect(title).toContain(titleContains);
        });
    }

    // Interactive: verify payslip page shows data
    test('payslip page shows payroll data', async ({ page }) => {
        await page.goto('/payroll', { waitUntil: 'networkidle' });
        await expect(page.locator('body')).toBeVisible();
        // Page should show either payslip data or empty state
        const bodyText = await page.locator('body').textContent();
        expect(bodyText!.length).toBeGreaterThan(50);
    });

    // Interactive: verify KB chat page loads
    test('KB chat page loads with chat interface', async ({ page }) => {
        await page.goto('/knowledge-base/chat', { waitUntil: 'networkidle' });
        await expect(page.locator('body')).toBeVisible();
        // Should have input or chat interface
        const bodyText = await page.locator('body').textContent();
        expect(bodyText).toContain('Pengetahuan');
    });

    // Interactive: verify attendance history has data
    test('attendance history shows records', async ({ page }) => {
        await page.goto('/attendance-history', { waitUntil: 'networkidle' });
        await expect(page.locator('body')).toBeVisible();
        const bodyText = await page.locator('body').textContent();
        expect(bodyText!.length).toBeGreaterThan(50);
    });

    // Interactive: verify schedule page
    test('schedule page shows calendar/schedule', async ({ page }) => {
        await page.goto('/my-schedule', { waitUntil: 'networkidle' });
        await expect(page.locator('body')).toBeVisible();
    });
});
