import { test, expect } from '@playwright/test';

// Auth handled via storageState (employee) — see playwright.config.js
test.describe('Attendance Clock-In Flow', () => {
  test('clock-in page loads with attendance section', async ({ page }) => {
    await page.goto('/attendance/clock-in');

    // Location section selalu muncul
    await expect(page.locator('text=Lokasi Saat Ini')).toBeVisible({ timeout: 8000 });

    // WFA mode toggle selalu muncul
    await expect(page.locator('text=Mode WFA')).toBeVisible();
  });

  test('WFA mode toggle works', async ({ page }) => {
    await page.goto('/attendance/clock-in');

    const checkbox = page.locator('input[type="checkbox"]').first();
    await expect(checkbox).toBeVisible({ timeout: 8000 });
    await expect(checkbox).not.toBeChecked();

    await checkbox.check({ force: true });
    await expect(checkbox).toBeChecked();

    await checkbox.uncheck({ force: true });
    await expect(checkbox).not.toBeChecked();
  });

  test('clock-in page shows status indicator', async ({ page }) => {
    await page.goto('/attendance/clock-in');
    await expect(page.locator('[role="status"]')).toBeVisible({ timeout: 8000 });
  });
});
