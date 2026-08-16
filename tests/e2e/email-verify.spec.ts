import { execSync } from 'child_process';
import { expect, test } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

/**
 * Regression E2E — alur verifikasi email lengkap:
 *   login unverified → /email/verify → resend → link → verified
 *
 * Mail di dev memakai SMTP (tidak bisa dibaca Playwright), jadi link/kode
 * email diambil dari helper PHP (tests/e2e/helpers/email-verify-helper.php)
 * yang mereproduksi PERSIS isi email (signed URL verification.verify + kode
 * 6 digit). Test ini guard: (1) Fortify redirect unverified ke /email/verify,
 * (2) form kode 6 digit di halaman verify berfungsi, (3) resend menampilkan
 * status + men-generate kode baru, (4) link dari email menandai verified.
 */
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const helper = path.join(__dirname, 'helpers/email-verify-helper.php');

const EMAIL = 'e2e-verify@hrconnect.test';
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
    page.waitForURL(/\/email\/verify/),
    page.click('button[type="submit"]'),
  ]);
}

test.describe('email verification flow', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeEach(() => {
    phpHelper('setup');
  });

  test('unverified login lands on /email/verify with code form + resend', async ({ page }) => {
    await login(page);

    await expect(page).toHaveURL(/\/email\/verify/);
    // Form kode 6 digit (fitur baru) tampil
    await expect(page.locator('input[name="code"]')).toBeVisible();
    await expect(page.locator('input[name="code"]')).toHaveAttribute('maxlength', '6');
    // Tombol resend tersedia
    await expect(
      page.locator('form[action*="verification-notification"] button[type="submit"]'),
    ).toBeVisible();
  });

  test('resend shows success banner and regenerates code', async ({ page }) => {
    await login(page);

    await page.locator('form[action*="verification-notification"] button[type="submit"]').click();

    // Banner sukses muncul (status verification-link-sent) — selector
    // struktural (border-emerald-200) supaya locale-agnostic (id/en).
    await expect(page.locator('div.border-emerald-200')).toBeVisible();
    // Kode baru ter-generate di DB
    const status = phpHelper('status');
    expect(status).toContain('code:yes');
  });

  test('verification link from email marks account verified', async ({ page }) => {
    await login(page);

    // Link persis seperti di email (signed URL)
    const verifyUrl = phpHelper('url');
    expect(verifyUrl).toContain('/email/verify/');

    await page.goto(verifyUrl);

    // Redirect keluar dari /email/verify → home
    await page.waitForURL((url) => !url.pathname.includes('/email/verify'), { timeout: 15_000 });
    // DB: email_verified_at ter-set
    await expect
      .poll(() => phpHelper('status'))
      .toContain('verified:yes');
  });

  test('6-digit code verifies account (wrong code rejected first)', async ({ page }) => {
    phpHelper('code', '424242');
    await login(page);

    // Kode salah → error tampil (p[role=alert] struktural, locale-agnostic),
    // tetap di halaman verify
    await page.fill('input[name="code"]', '000000');
    await page.click('form[action*="verify-code"] button[type="submit"]');
    await expect(page.locator('p[role="alert"]')).toBeVisible();
    await expect(page).toHaveURL(/\/email\/verify/);

    // Kode benar → verified + redirect home
    await page.fill('input[name="code"]', '424242');
    await Promise.all([
      page.waitForURL((url) => !url.pathname.includes('/email/verify'), { timeout: 15_000 }),
      page.click('form[action*="verify-code"] button[type="submit"]'),
    ]);
    await expect
      .poll(() => phpHelper('status'))
      .toContain('verified:yes');
  });
});
