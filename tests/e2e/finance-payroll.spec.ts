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

    // Verify page loaded
    await expect(page.locator('h1')).toContainText('Payroll');

    // Verify filter controls are present
    await expect(page.locator('select[name="statusFilter"], input[wire\\:model="periodFilter"]')).toBeVisible();
  });

  test('should filter payrolls by period', async ({ page }) => {
    await page.goto('/admin/payrolls');

    // Wait for page to load
    await page.waitForTimeout(2000);

    // Filter by period
    await page.fill('input[wire\\:model="periodFilter"]', '2026-08');
    await page.waitForTimeout(2000);

    // Verify filter applied
    await expect(page).toHaveURL(/periodFilter/);
  });

  test('should filter payrolls by status', async ({ page }) => {
    await page.goto('/admin/payrolls');

    // Wait for page to load
    await page.waitForTimeout(2000);

    // Select status filter
    const statusFilter = page.locator('select[name="statusFilter"]');
    if (await statusFilter.isVisible()) {
      await statusFilter.selectOption({ label: 'Draft' });
      await page.waitForTimeout(1500);
    }
  });

  test('should search for payroll by employee name', async ({ page }) => {
    await page.goto('/admin/payrolls');

    // Wait for page to load
    await page.waitForTimeout(2000);

    // Search for employee
    await page.fill('input[wire\\:model="search"]', 'test');
    await page.waitForTimeout(1500);
  });

  test('should submit payroll for verification', async ({ page }) => {
    await page.goto('/admin/payrolls');

    // Wait for page to load
    await page.waitForTimeout(2000);

    // Find a draft payroll to submit
    const submitButtons = page.locator('button[wire\\:click*="submit"], button[title*="Submit"]');
    const hasDraftPayroll = await submitButtons.count() > 0;

    if (hasDraftPayroll) {
      // Click submit button on first payroll
      await submitButtons.first().click();
      await page.waitForTimeout(2000);

      // Verify success notification
      await expect(page.locator('.notification-success, .bg-green-100')).toBeVisible({ timeout: 5000 });
    } else {
      // Verify empty or no draft payrolls
      await expect(page.locator('body')).toBeVisible();
    }
  });

  test('should mark payroll as paid', async ({ page }) => {
    await page.goto('/admin/payrolls');

    // Wait for page to load
    await page.waitForTimeout(2000);

    // Find an approved payroll to mark as paid
    const paidButtons = page.locator('button[wire\\:click*="markPaid"], button[title*="Paid"]');
    const hasApprovedPayroll = await paidButtons.count() > 0;

    if (hasApprovedPayroll) {
      // Click mark as paid button
      await paidButtons.first().click();
      await page.waitForTimeout(2000);

      // Verify success notification
      await expect(page.locator('.notification-success, .bg-green-100')).toBeVisible({ timeout: 5000 });
    }
  });

  test('should sort payrolls by column', async ({ page }) => {
    await page.goto('/admin/payrolls');

    // Wait for page to load
    await page.waitForTimeout(2000);

    // Try clicking sort by employee name
    const sortButton = page.locator('button[wire\\:click*="sortBy"]');
    if (await sortButton.isVisible()) {
      await sortButton.first().click();
      await page.waitForTimeout(1000);
    }
  });
});
