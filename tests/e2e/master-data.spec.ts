import { test, expect } from '@playwright/test';

/**
 * Master Data E2E — verifies HR role can access all master data pages.
 * Uses storageState from auth.setup.ts (hr@hrconnect.test → hr-manager role).
 */
test.describe('Master Data Pages (HR)', () => {
  test('branches page loads with Livewire component', async ({ page }) => {
    await page.goto('/master-data/branches');
    await expect(page.locator('text=Kelola data cabang')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('[wire\\:id]').first()).toBeVisible({ timeout: 5000 });
  });

  test('departments page loads', async ({ page }) => {
    await page.goto('/master-data/departments');
    // Page exists — no 403
    await expect(page.locator('[wire\\:id]').first()).toBeVisible({ timeout: 10000 });
  });

  test('positions page loads', async ({ page }) => {
    await page.goto('/master-data/positions');
    await expect(page.locator('[wire\\:id]').first()).toBeVisible({ timeout: 10000 });
  });

  test('shifts page loads', async ({ page }) => {
    await page.goto('/master-data/shifts');
    // "Shift Kerja" page title
    await expect(page.locator('[wire\\:id]').first()).toBeVisible({ timeout: 10000 });
    await expect(page.locator('h1:has-text("Shift")')).toBeVisible({ timeout: 5000 });
  });

  test('holidays page loads', async ({ page }) => {
    await page.goto('/master-data/holidays');
    await expect(page.locator('text=Kelola hari libur')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('[wire\\:id]').first()).toBeVisible({ timeout: 5000 });
  });
});
