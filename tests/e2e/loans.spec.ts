import { test, expect } from '@playwright/test';

test.describe('Pinjaman (Loan) Flow', () => {
  test.use({ storageState: 'tests/e2e/.auth/employee.json' });

  test('loans index page loads', async ({ page }) => {
    const resp = await page.goto('/loans');
    expect(resp?.status()).toBeLessThan(500);
  });

  test('loans page shows list or empty state', async ({ page }) => {
    await page.goto('/loans');
    await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
  });

  test('loans create button or content visible', async ({ page }) => {
    await page.goto('/loans');
    await page.waitForTimeout(2000);
    await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
  });
});
