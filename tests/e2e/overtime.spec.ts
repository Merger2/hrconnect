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
    // Wait for Livewire render — confirm URL, not error
    await expect(page.locator('[x-data="overtimeApply()"]')).toBeVisible({ timeout: 15000 });
  });

  test('create overtime button navigates to apply', async ({ page }) => {
    await page.goto('/overtimes');
    await page.locator('a[href*="overtimes/apply"]').first().click();
    await expect(page).toHaveURL(/\/overtimes\/apply/, { timeout: 5000 });
  });
});
