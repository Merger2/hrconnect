import { execSync } from 'child_process';
import { expect, test } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

/**
 * Audit E2E — alur register mandiri lengkap:
 *   /register (form) → user baru dibuat → /email/verify → link email → login
 *
 * Fokus audit: CreateNewUser hanya membuat User (group='user') TANPA Employee
 * record dan TANPA role. Verifikasi apakah /home + modul user utama tetap
 * render (200, tanpa 500/JS error) untuk akun register mandiri seperti itu.
 */
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const helper = path.join(__dirname, 'helpers/register-helper.php');

const EMAIL = 'e2e-register@hrconnect.test';
const NAME = 'E2E Register User';
const PASSWORD = 'password';

function phpHelper(...args: string[]): string {
  return execSync(`php ${helper} ${args.map((a) => `"${a}"`).join(' ')}`, {
    encoding: 'utf8',
  }).trim();
}

/** Isi form register; checkbox terms hanya jika dirender (fitur opsional). */
async function fillRegisterForm(page: import('@playwright/test').Page): Promise<void> {
  await page.fill('input[name="name"]', NAME);
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await page.fill('input[name="password_confirmation"]', PASSWORD);
  if ((await page.locator('input[name="terms"]').count()) > 0) {
    await page.check('input[name="terms"]');
  }
}

/** Register via UI → redirect /email/verify. */
async function registerViaUi(page: import('@playwright/test').Page): Promise<void> {
  await page.goto('/register');
  await expect(page).toHaveURL(/\/register$/);
  await fillRegisterForm(page);
  await Promise.all([
    page.waitForURL(/\/email\/verify/, { timeout: 15_000 }),
    page.click('button[type="submit"]'),
  ]);
}

test.describe('self-registration flow', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeEach(() => {
    phpHelper('reset');
  });

  test('register form creates user without employee record and redirects to verify', async ({ page }) => {
    await registerViaUi(page);

    // User ter-create: group=user, TANPA employee, TANPA role, unverified
    const status = phpHelper('status', EMAIL);
    expect(status).toContain('exists:yes');
    expect(status).toContain('group:user');
    expect(status).toContain('verified:no');
    expect(status).toContain('employee:no');
    expect(status).toContain('role:-');
  });

  test('verification link marks account verified', async ({ page }) => {
    // Setup ulang: register via UI
    await registerViaUi(page);

    const verifyUrl = phpHelper('url', EMAIL);
    expect(verifyUrl).toContain('/email/verify/');

    await page.goto(verifyUrl);
    await page.waitForURL((url) => !url.pathname.includes('/email/verify'), { timeout: 15_000 });

    await expect
      .poll(() => phpHelper('status', EMAIL))
      .toContain('verified:yes');
  });

  test('verified self-registered user (no employee) can reach /home without 500', async ({ page }) => {
    // Register + verify via link
    await registerViaUi(page);
    await page.goto(phpHelper('url', EMAIL));
    await page.waitForURL((url) => !url.pathname.includes('/email/verify'), { timeout: 15_000 });

    // /home harus render (200), bukan 500
    const resp = await page.goto('/home', { waitUntil: 'domcontentloaded' });
    expect(resp?.status()).toBe(200);
    await expect(page.locator('h1#home-page-title')).toContainText(NAME);
  });

  test('self-registered user can log out and log back in with verified account', async ({ page }) => {
    // Register + verify
    await registerViaUi(page);
    await page.goto(phpHelper('url', EMAIL));
    await page.waitForURL((url) => !url.pathname.includes('/email/verify'), { timeout: 15_000 });

    // Logout via POST /logout (route Fortify = POST; fetch pakai cookie
    // session yang sama + token CSRF dari meta tag)
    await page.evaluate(async () => {
      const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
      await fetch('/logout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token ?? '' },
      });
    });
    await page.goto('/login');
    await expect(page).toHaveURL(/\/login/);

    // Login ulang — verified → /home (bukan /email/verify)
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASSWORD);
    await Promise.all([
      page.waitForURL(/\/home/, { timeout: 15_000 }),
      page.click('button[type="submit"]'),
    ]);
    await expect(page).toHaveURL(/\/home/);
  });
});
