import { test, expect } from '@playwright/test';

test.describe('Direktori Karyawan (Employee Directory)', () => {
  test('employee index page loads', async ({ page }) => {
    await page.goto('/admin/employees');
    await expect(page.locator('text=Direktori Karyawan').first()).toBeVisible({ timeout: 10000 });
  });

  test('employee create page loads', async ({ page }) => {
    const resp = await page.goto('/admin/employees/create');
    expect(resp?.status()).toBeLessThan(400);
  });
});
