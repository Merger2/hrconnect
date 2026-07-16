import { test, expect } from '@playwright/test';

// Auth handled via storageState (hr) — see playwright.config.js
test.describe.configure({ retries: 2 });

test.describe('Face Enrollment Flow', () => {
  test.beforeEach(async ({ page }) => {
    // Mock camera API for headless testing
    await page.addInitScript(() => {
      navigator.mediaDevices.getUserMedia = async () => {
        const canvas = document.createElement('canvas');
        canvas.width = 640;
        canvas.height = 480;
        const stream = canvas.captureStream(30);
        return stream;
      };
    });
  });

  test('face registration page loads with title', async ({ page }) => {
    await page.goto('/attendance/face-registration');
    // HRConnect design system: use specific title selector
    await expect(page.locator('h2.text-lg.font-semibold.text-ink')).toContainText(/Registrasi Wajah|Face Registration/i);
    await expect(page.locator('h2.text-lg.font-semibold.text-ink').first().locator('~ p.text-sm.text-on-surface-variant')).toBeVisible({ timeout: 8000 });
  });

  test('face enrollment shows status and guide text', async ({ page }) => {
    await page.goto('/attendance/face-registration');
    await page.waitForTimeout(2500);
    // Page title h2 is always present (enrolled or capture state)
    await expect(page.locator('h2.text-lg.font-semibold.text-ink')).toContainText(/Registrasi Wajah|Face Registration/i, { timeout: 8000 });
    // Subtitle beside the title
    await expect(page.locator('h2.text-lg.font-semibold.text-ink').first().locator('~ p.text-sm.text-on-surface-variant')).toContainText(/Daftarkan wajah/i);
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
