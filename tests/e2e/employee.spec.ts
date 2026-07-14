import { test, expect } from '@playwright/test';

test.describe('Direktori Karyawan (Employee Directory)', () => {
  test.use({ storageState: 'tests/e2e/.auth/admin.json' });

  test('employee index page loads', async ({ page }) => {
    await page.goto('/admin/employees');
    await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
  });

  test('employee create page loads', async ({ page }) => {
    const resp = await page.goto('/admin/employees/create');
    expect(resp?.status()).toBeLessThan(500);
    await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
  });
});
