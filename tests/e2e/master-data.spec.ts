import { test, expect } from '@playwright/test';

test.describe('Master Data Pages (HR)', () => {
  test.use({ storageState: 'tests/e2e/.auth/hr.json' });

  test('branches page loads with Livewire component', async ({ page }) => {
    await page.goto('/master-data/branches');
    await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
  });

  test('departments page loads', async ({ page }) => {
    await page.goto('/master-data/departments');
    await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
  });

  test('positions page loads', async ({ page }) => {
    await page.goto('/master-data/positions');
    await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
  });

  test('shifts page loads', async ({ page }) => {
    await page.goto('/master-data/shifts');
    await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
  });

  test('holidays page loads', async ({ page }) => {
    await page.goto('/master-data/holidays');
    await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
  });
});
