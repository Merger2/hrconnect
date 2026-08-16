import { expect, test } from '@playwright/test';

/**
 * Regression E2E — self-registration DISABLED (keputusan Fikih 2026-08-12):
 * HRIS internal perusahaan, akun dibuat admin/HR, bukan register publik.
 *
 * Guard: (1) GET /register → 404, (2) POST /register → 404 (tidak ada user
 * dibuat), (3) halaman login tidak menampilkan link "Create account".
 */
test.describe('self-registration disabled', () => {
  test('GET /register returns 404 (feature disabled)', async ({ page }) => {
    const resp = await page.goto('/register', { waitUntil: 'domcontentloaded' });
    expect(resp?.status()).toBe(404);
  });

  test('POST /register is rejected', async ({ request }) => {
    const resp = await request.post('/register', {
      form: {
        name: 'Intruder',
        email: 'e2e-intruder@hrconnect.test',
        password: 'password',
        password_confirmation: 'password',
      },
    });
    expect(resp.status()).toBe(404);
  });

  test('login page does not link to registration', async ({ page }) => {
    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('a[href="/register"]')).toHaveCount(0);
  });
});
