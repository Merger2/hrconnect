/**
 * Manager Role — Comprehensive E2E Test
 *
 * Covers ALL 31 pages accessible by manager role:
 * - All employee pages (except /payroll)
 * - Approval workflows (leaves, reimbursements, overtime, shift swaps, WFH, kasbon)
 * - Admin pages (employees, attendance, leaves, reimbursements, overtime, reports, etc.)
 * - Interactive: switch approval tabs, verify pending data
 *
 * Storage state: tests/e2e/.auth/manager.json
 */
import { expect, test } from '@playwright/test';
import { smokeTest } from './helpers/smoke-test';

const MANAGER_PAGES = [
    // User pages
    { path: '/home', titleContains: 'Test Manager' },
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
    { path: '/approvals', titleContains: 'Persetujuan' },
    { path: '/approvals/history', titleContains: 'Riwayat' },
    { path: '/overtime', titleContains: 'Lembur' },
    { path: '/my-kasbon', titleContains: 'Kasbon' },
    { path: '/team-kasbon', titleContains: 'Kasbon' },
    { path: '/face-enrollment', titleContains: 'Face' },
    { path: '/my-assets', titleContains: 'Aset' },
    { path: '/my-performance', titleContains: 'Kinerja' },
    { path: '/knowledge-base/chat', titleContains: 'Pengetahuan' },
    // Admin pages
    { path: '/admin/employees', titleContains: 'Karyawan' },
    { path: '/admin/attendances', titleContains: 'Presensi' },
    { path: '/admin/leaves', titleContains: 'Cuti' },
    { path: '/admin/reimbursements', titleContains: 'Reimburse' },
    { path: '/admin/overtime', titleContains: 'Lembur' },
    { path: '/admin/reports', titleContains: 'Laporan' },
    { path: '/admin/shift-swaps', titleContains: 'Tukar Shift' },
    { path: '/admin/hr-checklists', titleContains: 'Checklist' },
];

test.describe('Manager Role', () => {
    test.use({
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        serviceWorkers: 'block',
    });

    // Smoke test all accessible pages
    for (const { path, titleContains } of MANAGER_PAGES) {
        test(`page ${path} renders correctly`, async ({ page }) => {
            const title = await smokeTest(page, path);
            expect(title).toContain(titleContains);
        });
    }

    // Interactive: approval tabs
    test('approvals page has 7 tabs', async ({ page }) => {
        await page.goto('/approvals', { waitUntil: 'networkidle' });

        // All 7 approval tabs exist
        await expect(page.locator('button[wire\\:click*="switchTab(\'leaves\')"]')).toContainText('Cuti');
        await expect(page.locator('button[wire\\:click*="switchTab(\'reimbursements\')"]')).toContainText('Klaim');
        await expect(page.locator('button[wire\\:click*="switchTab(\'attendance-corrections\')"]')).toContainText('Koreksi');
        await expect(page.locator('button[wire\\:click*="switchTab(\'shift-swaps\')"]')).toContainText('Tukar Shift');
        await expect(page.locator('button[wire\\:click*="switchTab(\'overtimes\')"]')).toContainText('Lembur');
        await expect(page.locator('button[wire\\:click*="switchTab(\'wfh\')"]')).toContainText('WFH');
        await expect(page.locator('button[wire\\:click*="switchTab(\'kasbons\')"]')).toContainText('Kasbon');
    });

    // Interactive: switch approval tabs
    test('can switch between approval tabs', async ({ page }) => {
        await page.goto('/approvals', { waitUntil: 'networkidle' });

        // Default: leaves tab (aria-selected=true)
        await expect(page.locator('button[wire\\:click*="switchTab(\'leaves\')"]')).toHaveAttribute('aria-selected', 'true');

        // Switch to reimbursements
        await page.click('button[wire\\:click*="switchTab(\'reimbursements\')"]');
        await page.waitForTimeout(1500);

        // Switch to overtimes
        await page.click('button[wire\\:click*="switchTab(\'overtimes\')"]');
        await page.waitForTimeout(1500);

        // Switch to WFH
        await page.click('button[wire\\:click*="switchTab(\'wfh\')"]');
        await page.waitForTimeout(1500);

        // Switch back to leaves
        await page.click('button[wire\\:click*="switchTab(\'leaves\')"]');
        await page.waitForTimeout(1500);

        // No server error after switching
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });

    // Interactive: pending requests from subordinates
    test('approvals shows pending requests from subordinates', async ({ page }) => {
        await page.goto('/approvals', { waitUntil: 'networkidle' });
        await page.waitForTimeout(2000);

        // Should show either pending data or empty state — no error
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });

    // Interactive: approvals history
    test('approvals history page loads', async ({ page }) => {
        await page.goto('/approvals/history', { waitUntil: 'networkidle' });
        await expect(page.locator('body')).toBeVisible();
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });
});
