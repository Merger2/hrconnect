import { execSync } from 'child_process';
import { expect, test } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

/**
 * Regression E2E — alur two-factor authentication lengkap:
 *   login (2FA confirmed) → /two-factor-challenge → OTP → home
 *   login → recovery code → home
 *   OTP salah → error ditolak
 *
 * OTP TOTP 6 digit di-generate dari secret pengguna (persis Google
 * Authenticator). Helper PHP (tests/e2e/helpers/twofa-helper.php) membuat
 * user 2FA e2e-2fa@hrconnect.test (secret base32 valid + 8 recovery codes +
 * confirmed), menghapus session + reset rate limiter sebelum tiap test
 * (Laravel 13 hashes keys: login md5('login'.'email|ip'), two-factor
 * md5('two-factor'.user_id), sha1(user_id) untuk anon throttle).
 */
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const helper = path.join(__dirname, 'helpers/twofa-helper.php');

const EMAIL = 'e2e-2fa@hrconnect.test';
const PASSWORD = 'password';

function phpHelper(...args: string[]): string {
  return execSync(`php ${helper} ${args.map((a) => `"${a}"`).join(' ')}`, {
    encoding: 'utf8',
  }).trim();
}

async function login(page: import('@playwright/test').Page): Promise<void> {
  await page.goto('/login');
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await Promise.all([
    page.waitForURL(/two-factor-challenge/),
    page.click('button[type="submit"]'),
  ]);
}

// Login saat 2FA NONAKTIF (langsung ke /home, tanpa challenge).
async function loginWithout2fa(page: import('@playwright/test').Page): Promise<void> {
  await page.goto('/login');
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await Promise.all([
    page.waitForURL(/\/(home|dashboard)$/, { timeout: 15_000 }),
    page.click('button[type="submit"]'),
  ]);
}

test.describe('two-factor authentication flow', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeEach(() => {
    phpHelper('setup');
  });

  test('2FA-confirmed login lands on challenge page with OTP input', async ({ page }) => {
    await login(page);

    await expect(page).toHaveURL(/two-factor-challenge/);
    await expect(page.locator('input[name="code"]')).toBeVisible();
    await expect(page.locator('input[name="code"]')).toHaveAttribute('inputmode', 'numeric');
    // Toggle recovery code tersedia (button[type=button] pertama = "Use a
    // recovery code" / "Gunakan kode pemulihan") — locale-agnostic.
    await expect(page.locator('button[type="button"]').first()).toBeVisible();
  });

  test('wrong OTP is rejected and user stays on challenge', async ({ page }) => {
    await login(page);

    await page.fill('input[name="code"]', '000000');
    await page.click('button[type="submit"]');

    // Error box (border-red-200, locale-agnostic) tampil, tetap di challenge
    await expect(page.locator('div.border-red-200')).toBeVisible();
    await expect(page).toHaveURL(/two-factor-challenge/);
  });

  test('valid OTP from authenticator logs the user in', async ({ page }) => {
    await login(page);

    // Kode TOTP saat ini — persis yang dihasilkan Google Authenticator
    const otp = phpHelper('otp');
    expect(otp).toMatch(/^\d{6}$/);

    await page.fill('input[name="code"]', otp);
    await Promise.all([
      page.waitForURL(/\/home$/, { timeout: 15_000 }),
      page.click('button[type="submit"]'),
    ]);

    await expect(page).toHaveURL(/\/home$/);
    // 2FA tetap aktif setelah login
    await expect.poll(() => phpHelper('status')).toContain('2fa:yes');
  });

  test('recovery code logs the user in', async ({ page }) => {
    await login(page);

    // Buka mode recovery code (button[type=button] pertama, locale-agnostic)
    await page.locator('button[type="button"]').first().click();
    await expect(page.locator('input[name="recovery_code"]')).toBeVisible();

    const recoveryCode = phpHelper('recovery');
    expect(recoveryCode).toContain('E2EFA-');

    await page.fill('input[name="recovery_code"]', recoveryCode);
    await Promise.all([
      page.waitForURL(/\/home$/, { timeout: 15_000 }),
      page.click('button[type="submit"]'),
    ]);

    await expect(page).toHaveURL(/\/home$/);
    // Recovery code yang dipakai di-rotasi (tidak bisa dipakai ulang)
    await expect.poll(() => phpHelper('recovery')).not.toContain(recoveryCode);
  });

  test('enable flow: Enable → password confirm modal → OTP → recovery codes shown', async ({ page }) => {
    // Disable 2FA dulu supaya alur enable bisa diuji (helper setup men-set 2FA aktif)
    phpHelper('disable');

    await loginWithout2fa(page);

    await page.goto('/user/profile', { timeout: 20_000 });
    await page.waitForLoadState('domcontentloaded');

    // Section 2FA (locale-agnostic)
    await expect(page.getByText(/Autentikasi Dua Faktor|Two Factor Authentication/).first()).toBeVisible();

    // Klik Enable / Aktifkan
    await page.locator('button', { hasText: /Aktifkan|Enable/ }).first().click();

    // Modal konfirmasi password MUNCUL (regresi: dulu inline tanpa modal → mati)
    await expect(page.locator('input[x-ref="confirmable_password"]')).toBeVisible();
    await page.fill('input[x-ref="confirmable_password"]', PASSWORD);
    await page.locator('[dusk="confirm-password-button"]').click();

    // QR/setup + input OTP tampil
    await expect(page.locator('input[name="code"]')).toBeVisible({ timeout: 10_000 });

    // Konfirmasi OTP valid → two_factor_confirmed_at ter-set + recovery codes tampil
    const otp = phpHelper('otp');
    await page.fill('input[name="code"]', otp);
    await page.keyboard.press('Enter');
    await expect(page.getByText(/Recovery|Pemulihan/).first()).toBeVisible({ timeout: 10_000 });
    await expect.poll(() => phpHelper('status')).toContain('2fa:yes');
  });
});
