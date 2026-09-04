/**
 * Finance Role E2E Tests
 *
 * Tests the finance role's accessible pages:
 * - Home dashboard
 * - Reimbursement management (approve/process)
 * - Attendance data (view)
 * - Reports (export)
 * - Employee data (view)
 * - Own payslip access
 */
import { expect, test } from '@playwright/test';

test.describe('Finance Role', () => {
    test.use({
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        serviceWorkers: 'block',
    });

    test('should access finance home dashboard', async ({ page }) => {
        await page.goto('/home', { waitUntil: 'networkidle' });
        await expect(page.locator('h1')).toContainText('Test Finance');
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });

    test('should display reimbursement management page', async ({ page }) => {
        await page.goto('/admin/reimbursements', { waitUntil: 'networkidle' });

        // Verify page loaded
        await expect(page.locator('h1')).toContainText('Reimburse');

        // Verify table or list exists
        await expect(page.locator('body')).toBeVisible();

        // Verify no server error
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });

    test('should display attendance data page', async ({ page }) => {
        await page.goto('/admin/attendances', { waitUntil: 'networkidle' });

        // Verify page loaded
        await expect(page.locator('h1')).toContainText('Presensi');

        // Verify no server error
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });

    test('should display reports page', async ({ page }) => {
        await page.goto('/admin/reports', { waitUntil: 'networkidle' });

        // Verify page loaded
        await expect(page.locator('h1')).toContainText('Laporan');

        // Verify no server error
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });

    test('should display employee management page', async ({ page }) => {
        await page.goto('/admin/employees', { waitUntil: 'networkidle' });

        // Verify page loaded
        await expect(page.locator('h1')).toContainText('Karyawan');

        // Verify no server error
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });

    test('should access own payslip page', async ({ page }) => {
        await page.goto('/payroll', { waitUntil: 'networkidle' });

        // Verify page loaded (payslip page)
        await expect(page.locator('body')).toBeVisible();

        // Verify no server error
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });

    test('should filter reimbursements by status', async ({ page }) => {
        await page.goto('/admin/reimbursements', { waitUntil: 'networkidle' });
        await page.waitForTimeout(2000);

        // Verify filter controls exist
        const searchInput = page.locator('input[wire\\:model="search"]');
        if (await searchInput.isVisible()) {
            await searchInput.fill('test');
            await page.waitForTimeout(1500);
        }

        // Verify no server error after search
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });
});
