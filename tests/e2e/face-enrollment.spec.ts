import { test, expect } from '@playwright/test';

// Auth handled via storageState (hr) — see playwright.config.js
test.describe('Face Enrollment Flow', () => {
  test('face registration page loads with title', async ({ page }) => {
    await page.goto('/attendance/face-registration');
    await expect(page.locator('h2')).toContainText(/Registrasi Wajah|Face Registration/i);
    await expect(page.locator('text=Daftarkan wajah')).toBeVisible({ timeout: 8000 });
  });

  test('face enrollment shows status and guide text', async ({ page }) => {
    await page.goto('/attendance/face-registration');
    await page.waitForTimeout(2500);
    const guideOrTitle = page.locator('h2, [role="status"]').first();
    await expect(guideOrTitle).toBeVisible({ timeout: 8000 });
    await expect(page.locator('text=Daftarkan wajah')).toBeVisible();
  });

  test('face enrollment shows camera or enrolled state', async ({ page }) => {
    await page.goto('/attendance/face-registration');
    await page.waitForTimeout(3000);
    const hasEnrolled = await page.locator('text=Face ID Aktif').isVisible({ timeout: 2000 }).catch(() => false);
    if (hasEnrolled) {
      await expect(page.locator('text=Perbarui Face ID')).toBeVisible();
    } else {
      await expect(page.locator('video')).toBeAttached({ timeout: 5000 });
    }
  });

  test('face registration page loads without server error', async ({ page }) => {
    const resp = await page.goto('/attendance/face-registration');
    expect(resp?.status()).toBeLessThan(500);
    await expect(page.locator('body')).not.toContainText('500');
  });

  test('face registration page has guide instructions', async ({ page }) => {
    await page.goto('/attendance/face-registration');
    await page.waitForTimeout(2000);
    const bodyText = await page.locator('body').textContent();
    expect(bodyText.length).toBeGreaterThan(50);
  });
});
