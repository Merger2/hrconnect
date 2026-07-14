import { test, expect } from '@playwright/test';

test.describe('Payroll Page (Admin)', () => {
  test('payroll index page loads', async ({ page }) => {
    await page.goto('/admin/payroll');
    await expect(page.locator('[wire\\:id]').first()).toBeVisible({ timeout: 10000 });
    await expect(page.locator('body')).not.toContainText('403');
    await expect(page.locator('body')).not.toContainText('500');
  });

  test('payroll index has heading', async ({ page }) => {
    await page.goto('/admin/payroll');
    await expect(page.locator('h1, h2').first()).toBeVisible({ timeout: 10000 });
  });

  test('payroll index loads without server error', async ({ page }) => {
    const resp = await page.goto('/admin/payroll');
    expect(resp?.status()).toBeLessThan(500);
  });
});

test.describe('Settings Page', () => {
  test('settings profile page loads', async ({ page }) => {
    await page.goto('/settings/profile');
    await expect(page.locator('[wire\\:id]').first()).toBeVisible({ timeout: 10000 });
    await expect(page.locator('body')).not.toContainText('403');
  });

  test('settings profile page has content', async ({ page }) => {
    await page.goto('/settings/profile');
    await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    const bodyText = await page.locator('body').textContent();
    expect(bodyText.length).toBeGreaterThan(50);
  });

  test('settings appearance page loads', async ({ page }) => {
    await page.goto('/settings/appearance');
    await expect(page.locator('[wire\\:id]').first()).toBeVisible({ timeout: 10000 });
  });
});
