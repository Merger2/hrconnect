import { test, expect } from '@playwright/test';

test.describe('Payroll Page (Admin)', () => {
  test('payroll index page loads', async ({ page }) => {
    await page.goto('/admin/payroll');
    await expect(page.locator('[wire\\:id]').first()).toBeVisible({ timeout: 10000 });
    await expect(page.locator('body')).not.toContainText('403');
    await expect(page.locator('body')).not.toContainText('500');
  });
});

test.describe('Settings Page', () => {
  test('settings profile page loads', async ({ page }) => {
    await page.goto('/settings/profile');
    await expect(page.locator('[wire\\:id]').first()).toBeVisible({ timeout: 10000 });
    await expect(page.locator('body')).not.toContainText('403');
  });
});
