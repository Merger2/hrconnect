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

    // Guide section (steps 1,2) or enrolled check — visible in either state
    const guideOrTitle = page.locator('h2, [role="status"]').first();
    await expect(guideOrTitle).toBeVisible({ timeout: 8000 });

    // Page description always rendered
    await expect(page.locator('text=Daftarkan wajah')).toBeVisible();
  });

  test('face enrollment shows camera or enrolled state', async ({ page }) => {
    await page.goto('/attendance/face-registration');
    await page.waitForTimeout(3000);

    // Either enrolled: "Face ID Aktif" with "Perbarui"/"Hapus" buttons
    // Or capture flow: video + guide instructions + "Rekam" button
    const hasEnrolled = await page.locator('text=Face ID Aktif').isVisible({ timeout: 2000 }).catch(() => false);

    if (hasEnrolled) {
      await expect(page.locator('text=Perbarui Face ID')).toBeVisible();
    } else {
      // Camera video element should exist in capture flow
      await expect(page.locator('video')).toBeAttached({ timeout: 5000 });
    }
  });
});
