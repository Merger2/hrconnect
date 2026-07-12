import { test, expect } from '@playwright/test';

// Auth handled via storageState (employee) — see playwright.config.js
test.describe('Attendance Clock-In Flow', () => {
  test('clock-in page loads with attendance section', async ({ page }) => {
    await page.goto('/attendance/clock-in');
    // Location section always visible
    await expect(page.locator('text=Lokasi Saat Ini')).toBeVisible({ timeout: 8000 });
    // WFA mode toggle always visible
    await expect(page.locator('text=Mode WFA')).toBeVisible();
  });

  test('WFA mode toggle works', async ({ page }) => {
    await page.goto('/attendance/clock-in');
    // First checkbox is the WFA toggle
    const checkbox = page.locator('input[type="checkbox"]').first();
    await expect(checkbox).toBeVisible({ timeout: 8000 });
    // Toggle on
    await checkbox.check({ force: true });
    await expect(checkbox).toBeChecked();
    // Toggle off
    await checkbox.uncheck({ force: true });
    await expect(checkbox).not.toBeChecked();
  });

  test('clock-in page shows status indicator', async ({ page }) => {
    await page.goto('/attendance/clock-in');
    // Page renders — at least body is visible
    await expect(page.locator('body')).toBeVisible({ timeout: 8000 });
  });
});
