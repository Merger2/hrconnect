import { test, expect } from '@playwright/test';

const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';

// Catatan 2026-09-06:
// - Dasbor employee menyapa dalam Bahasa Indonesia dan h1-nya adalah nama user
//   ("Selamat sore, <nama>"), bukan teks "Dashboard"/"Home" — jadi keberhasilan
//   login diverifikasi lewat URL /home (konvensi yang sama dengan twofa.spec.ts
//   dan workflow-approvals.spec.ts). User tanpa sesi akan di-redirect ke /login
//   oleh middleware auth, jadi URL /home sudah membuktikan sesi aktif.
// - Error login dirender oleh <x-forms.validation-errors> dengan role="alert";
//   teksnya terlokalisasi (id: "Identitas tersebut tidak cocok dengan data kami.")
//   di bawah header "Whoops! Something went wrong." — karenanya filter hasText
//   mencakup kedua kemungkinan bahasa.

test.describe('workflow: employee login', () => {
    test('employee can login with valid credentials', async ({ page }) => {
        await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });

        await page.locator('input[name="email"]').fill('employee@hrconnect.test');
        await page.locator('input[name="password"]').fill('password');
        await page.locator('button[type="submit"]').click();

        await expect(page).toHaveURL(/\/home$/, { timeout: 15000 });
    });

    test('login shows error for invalid email', async ({ page }) => {
        await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });

        await page.locator('input[name="email"]').fill('invalid@test.com');
        await page.locator('input[name="password"]').fill('password');
        await page.locator('button[type="submit"]').click();

        await expect(page.locator('[role="alert"]').filter({ hasText: /Whoops|tidak cocok|invalid/i })).toBeVisible({ timeout: 5000 });
    });

    test('login shows error for wrong password', async ({ page }) => {
        await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });

        await page.locator('input[name="email"]').fill('employee@hrconnect.test');
        await page.locator('input[name="password"]').fill('wrongpassword');
        await page.locator('button[type="submit"]').click();

        await expect(page.locator('[role="alert"]').filter({ hasText: /Whoops|tidak cocok|invalid/i })).toBeVisible({ timeout: 5000 });
    });

    test('login redirects to dashboard after success', async ({ page }) => {
        await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });

        await page.locator('input[name="email"]').fill('employee@hrconnect.test');
        await page.locator('input[name="password"]').fill('password');
        await page.locator('button[type="submit"]').click();

        await expect(page).toHaveURL(/\/home$/, { timeout: 15000 });
    });

    test('session persists after login', async ({ page }) => {
        await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });

        await page.locator('input[name="email"]').fill('employee@hrconnect.test');
        await page.locator('input[name="password"]').fill('password');
        await page.locator('button[type="submit"]').click();
        await expect(page).toHaveURL(/\/home$/, { timeout: 15000 });

        await page.goto(`${BASE}/home`, { waitUntil: 'domcontentloaded' });
        await expect(page).toHaveURL(/\/home$/, { timeout: 15000 });
    });
});
