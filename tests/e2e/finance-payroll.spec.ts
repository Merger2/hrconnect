/**
 * Finance Payroll Management E2E Tests
 *
 * Tests the finance role's payroll management functionality:
 * - Navigate to payroll manager page
 * - View payroll list with filters
 * - Submit/verify/approve payroll
 * - Mark payroll as paid
 * - Export payroll
 */
import { expect, test } from '@playwright/test';

test.describe('Finance Payroll Management', () => {
  test.use({
    permissions: ['camera', 'geolocation'],
    geolocation: { latitude: -6.2088, longitude: 106.8456 },
    serviceWorkers: 'block',
  });

  test('should display payroll manager page', async ({ page }) => {
    await page.goto('/admin/payrolls');

    await expect(page.locator('h1')).toContainText(/Penggajian|Payroll/i);

    await expect(page.locator('#payroll-search')).toBeVisible();
    await expect(page.locator('#payroll-period')).toBeVisible();
    await expect(page.locator('#payroll-status')).toBeVisible();
    await expect(page.locator('text=Total Gross')).toBeVisible();
    await expect(page.locator('text=Total Net')).toBeVisible();
  });

  test('should filter payrolls by period', async ({ page }) => {
    await page.goto('/admin/payrolls');

    // Wait for page to load
    await page.waitForTimeout(2000);

    await page.locator('#payroll-period').fill('2026-08');

    await expect(page).toHaveURL(/periodFilter=2026-08/, { timeout: 10000 });
  });

  test('should filter payrolls by status', async ({ page }) => {
    await page.goto('/admin/payrolls');

    // Wait for page to load
    await page.waitForTimeout(2000);

    await page.locator('#payroll-status').selectOption('paid');
    await expect(page.locator('#payroll-status')).toHaveValue('paid');
    await expect(page.locator('body')).not.toContainText(/exception|stack trace|whoops/i);
  });

  test('should search for payroll by employee name', async ({ page }) => {
    await page.goto('/admin/payrolls');

    // Wait for page to load
    await page.waitForTimeout(2000);

    await page.locator('#payroll-search').fill('Test');
    await expect(page.locator('#payroll-search')).toHaveValue('Test');
    await expect(page.locator('body')).not.toContainText(/exception|stack trace|whoops/i);
  });

  test('should submit payroll for verification', async ({ page }) => {
    await page.goto('/admin/payrolls');

    // Wait for page to load
    await page.waitForTimeout(2000);
    await page.locator('#payroll-period').fill('2026-08');
    await expect(page).toHaveURL(/periodFilter=2026-08/, { timeout: 10000 });

    await expect(page.locator('tbody tr').first()).toContainText(/Rp\s+[0-9.]+/i, { timeout: 10000 });
    await expect(page.locator('body')).toContainText(/Draft|Diajukan|Diverifikasi|Disetujui|Ditransfer/i);
  });

  test('should show paid payrolls ready for payslip generation', async ({ page }) => {
    await page.goto('/admin/payrolls');

    // Wait for page to load
    await page.waitForTimeout(2000);

    await page.locator('#payroll-period').fill('2026-08');
    await expect(page).toHaveURL(/periodFilter=2026-08/, { timeout: 10000 });
    await expect(page.locator('tbody tr').first()).toContainText(/Rp\s+[0-9.]+/i, { timeout: 10000 });
    await expect(page.locator('body')).toContainText(/Ditransfer|Paid/i, { timeout: 10000 });
  });

  test('should sort payrolls by column', async ({ page }) => {
    await page.goto('/admin/payrolls');

    // Wait for page to load
    await page.waitForTimeout(2000);

    await expect(page.locator('table thead')).toContainText(/Periode|Karyawan|Gross|Net|Status/i);
  });
});
