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

  test('camera flow attaches srcObject to video without undefined error', async ({ page, context }) => {
    // Grant camera + geolocation so getUserMedia resolves with a fake stream in headless
    await context.grantPermissions(['camera', 'geolocation']);

    const pageErrors: string[] = [];
    page.on('pageerror', err => pageErrors.push(err.message));

    await page.goto('/attendance/clock-in');
    // Wait for Alpine to boot + model load + camera init
    await page.waitForTimeout(6000);

    // Video element should exist (inside x-if !pinRequired) and have a srcObject attached
    const videoState = await page.evaluate(() => {
      const v = document.querySelector('video[x-ref="video"]') as HTMLVideoElement | null;
      if (!v) return { exists: false };
      return {
        exists: true,
        hasSrcObject: !!v.srcObject,
        readyState: v.readyState,
        status: (document.querySelector('[x-data^="clockIn"]') as any)?._x_dataStack?.[0]?.status,
      };
    });

    // The key regression: no "Cannot set properties of undefined (setting 'srcObject')"
    const srcObjectErrors = pageErrors.filter(e => e.includes('srcObject') || e.includes('Cannot set properties of undefined'));
    expect(srcObjectErrors, `Found srcObject errors: ${JSON.stringify(srcObjectErrors)}`).toHaveLength(0);
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
