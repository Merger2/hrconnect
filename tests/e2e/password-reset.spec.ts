import { execSync } from 'child_process';
import { expect, test } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

/**
 * Regression E2E — alur password reset lengkap:
 *   forgot-password → email link → reset form → login password baru
 *
 * Mail di dev memakai SMTP (tidak bisa dibaca Playwright), jadi link reset
 * diambil dari helper PHP (tests/e2e/helpers/password-reset-helper.php) yang
 * mereproduksi PERSIS isi email (route password.reset + token dari
 * DatabaseTokenRepository). Test ini guard: (1) forgot-password page render,
 * (2) submit email → status sukses, (3) link email membuka reset form dengan
 * email terisi, (4) reset password → login password baru sukses & password
 * lama ditolak, (5) password_changed_at ter-update pasca reset.
 */
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const helper = path.join(__dirname, 'helpers/password-reset-helper.php');

const EMAIL = 'e2e-reset@hrconnect.test';
const OLD_PASSWORD = 'OldPass!2026';
const NEW_PASSWORD = 'NewPass!2026';

function phpHelper(...args: string[]): string {
  return execSync(`php ${helper} ${args.map((a) => `"${a}"`).join(' ')}`, {
    encoding: 'utf8',
  }).trim();
}

test.describe('password reset flow', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeEach(() => {
    phpHelper('setup');
  });

  test('forgot password page renders with email form', async ({ page }) => {
    await page.goto('/forgot-password');

    await expect(page).toHaveURL(/\/forgot-password/);
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
    // Link balik ke login tersedia
    await expect(page.locator('a[href*="login"]').first()).toBeVisible();
  });

  test('submitting email shows success status banner', async ({ page }) => {
    await page.goto('/forgot-password');
    await page.fill('input[name="email"]', EMAIL);
    await page.click('button[type="submit"]');

    // Fortify redirect kembali ke forgot-password dengan status success
    // (border-emerald-200 = banner sukses, locale-agnostic).
    await expect(page).toHaveURL(/\/forgot-password/);
    await expect(page.locator('div.border-emerald-200')).toBeVisible();
  });

  test('reset link from email opens reset form with email prefilled', async ({ page }) => {
    const resetUrl = phpHelper('token');
    expect(resetUrl).toContain('/reset-password/');

    await page.goto(resetUrl);

    await expect(page).toHaveURL(/\/reset-password\//);
    // Email terisi dari query param (nilai dari old('email', $request->email))
    await expect(page.locator('input[name="email"]')).toHaveValue(EMAIL);
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('input[name="password_confirmation"]')).toBeVisible();
  });

  test('resetting password logs in with new password and refreshes password_changed_at', async ({ page }) => {
    const resetUrl = phpHelper('token');

    await page.goto(resetUrl);
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', NEW_PASSWORD);
    await page.fill('input[name="password_confirmation"]', NEW_PASSWORD);
    await page.click('button[type="submit"]');

    // Setelah reset, Fortify redirect ke login (default PasswordResetResponse)
    await page.waitForURL(/\/login/, { timeout: 15_000 });

    // Login dengan password BARU → sukses masuk /home
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', NEW_PASSWORD);
    await Promise.all([
      page.waitForURL((url) => url.pathname.includes('/home'), { timeout: 15_000 }),
      page.click('button[type="submit"]'),
    ]);
    await expect(page).toHaveURL(/\/home/);

    // password_changed_at disegarkan pasca reset (guard CheckPasswordExpired:
    // user yang baru reset harus dianggap segar, bukan "harus ganti password").
    // Setup menaruh 10 hari lalu → setelah reset harus < 5 menit.
    const status = phpHelper('status');
    expect(status).toContain('password_changed_at:yes');
    const minutes = Number(status.match(/changed_minutes_ago:(\d+)/)?.[1] ?? '999');
    expect(minutes).toBeLessThan(5);
  });

  test('old password is rejected after reset', async ({ page }) => {
    const resetUrl = phpHelper('token');

    await page.goto(resetUrl);
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', NEW_PASSWORD);
    await page.fill('input[name="password_confirmation"]', NEW_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/login/, { timeout: 15_000 });

    // Password lama ditolak → kembali ke /login (tidak masuk)
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', OLD_PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForTimeout(1500);
    await expect(page).toHaveURL(/\/login/);
  });
});
