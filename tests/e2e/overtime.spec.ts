import { test, expect } from '@playwright/test';

test.describe('Lembur (Overtime) Flow', () => {
  test('overtime index page loads', async ({ page }) => {
    await page.goto('/overtimes');
    await expect(page.locator('text=Riwayat pengajuan lembur Anda')).toBeVisible({ timeout: 10000 });
  });

  test('overtime page shows period filter', async ({ page }) => {
    await page.goto('/overtimes');
    await expect(page.locator('select[x-model="period"]')).toBeVisible({ timeout: 10000 });
  });

  test('overtime apply page loads', async ({ page }) => {
    await page.goto('/overtimes/apply');
    await expect(page.locator('[x-data="overtimeApply()"]')).toBeVisible({ timeout: 15000 });
  });

  test('create overtime button navigates to apply', async ({ page }) => {
    await page.goto('/overtimes');
    await page.locator('a[href*="overtimes/apply"]').first().click();
    await expect(page).toHaveURL(/\/overtimes\/apply/, { timeout: 5000 });
  });

  test('overtime index loads without server error', async ({ page }) => {
    const resp = await page.goto('/overtimes');
    expect(resp?.status()).toBeLessThan(500);
    await expect(page.locator('body')).not.toContainText('500');
  });

  test('overtime apply page loads without server error', async ({ page }) => {
    const resp = await page.goto('/overtimes/apply');
    expect(resp?.status()).toBeLessThan(500);
    await expect(page.locator('body')).not.toContainText('500');
  });

  test('overtime apply page has form fields', async ({ page }) => {
    await page.goto('/overtimes/apply');
    await page.waitForTimeout(2000);
    const formControls = page.locator('input, select, textarea');
    expect(await formControls.count()).toBeGreaterThan(0);
  });
});
