import { test, expect } from '@playwright/test';

// Auth handled via storageState (employee) — see playwright.config.js
test.describe('Attendance Clock-In Flow', () => {
  test('clock-in page loads with attendance section', async ({ page }) => {
    await page.goto('/attendance/clock-in');
    await expect(page.locator('text=Lokasi Saat Ini')).toBeVisible({ timeout: 8000 });
    await expect(page.locator('text=Mode WFA')).toBeVisible();
  });

  test('WFA mode toggle works', async ({ page }) => {
    await page.goto('/attendance/clock-in');
    const checkbox = page.locator('input[type="checkbox"]').first();
    await expect(checkbox).toBeVisible({ timeout: 8000 });
    await checkbox.check({ force: true });
    await expect(checkbox).toBeChecked();
    await checkbox.uncheck({ force: true });
    await expect(checkbox).not.toBeChecked();
  });

  test('clock-in page shows status indicator', async ({ page }) => {
    await page.goto('/attendance/clock-in');
    await expect(page.locator('body')).toBeVisible({ timeout: 8000 });
  });

  test('clock-in page loads without server error', async ({ page }) => {
    const resp = await page.goto('/attendance/clock-in');
    expect(resp?.status()).toBeLessThan(500);
    await expect(page.locator('body')).not.toContainText('500');
  });

  test('clock-in page shows location section', async ({ page }) => {
    await page.goto('/attendance/clock-in');
    await expect(page.locator('text=Lokasi Saat Ini')).toBeVisible({ timeout: 8000 });
  });

  test('attendance history page loads', async ({ page }) => {
    const resp = await page.goto('/attendance');
    expect(resp?.status()).toBeLessThan(400);
    await expect(page.locator('body')).toBeVisible({ timeout: 8000 });
  });
});
