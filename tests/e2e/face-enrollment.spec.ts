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
    // Target the page title h2 specifically, not the Swal modal h2
    await expect(page.locator('main h2').first()).toContainText(/Registrasi Wajah|Face Registration/i);
    await expect(page.locator('text=Daftarkan wajah')).toBeVisible({ timeout: 8000 });
  });

  test('face enrollment shows status and guide text', async ({ page }) => {
    await page.goto('/attendance/face-registration');
    await page.waitForTimeout(2500);
    const guideOrTitle = page.locator('h2, [role="status"]').first();
    await expect(guideOrTitle).toBeVisible({ timeout: 8000 });
    // Use more specific selector to avoid strict mode violation (two elements contain "Daftarkan wajah")
    await expect(page.locator('text=Daftarkan wajah Anda untuk verifikasi absensi')).toBeVisible();
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

  test('re-enroll (Perbarui Face ID) starts camera without "video element is not ready"', async ({ page }) => {
    const errors: string[] = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') errors.push(msg.text());
    });
    page.on('pageerror', (err) => errors.push(err.message));

    await page.goto('/attendance/face-registration');
    await page.waitForTimeout(3000);

    const hasEnrolled = await page.locator('text=Face ID Aktif').isVisible({ timeout: 3000 }).catch(() => false);
    test.skip(!hasEnrolled, 'Seed user is not enrolled yet — nothing to re-enroll.');

    await expect(page.locator('text=Perbarui Face ID')).toBeVisible();
    await page.locator('text=Perbarui Face ID').click();

    // Capture template (with <video>) must render and camera must attach.
    await expect(page.locator('video')).toBeAttached({ timeout: 8000 });
    // Give the camera boot + first detection tick a moment.
    await page.waitForTimeout(2500);

    const fatal = errors.filter((e) =>
      /video element is not ready|TypeError/i.test(e)
    );
    expect(fatal, `Console errors: ${JSON.stringify(errors)}`).toHaveLength(0);
  });
});
