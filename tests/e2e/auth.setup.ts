/**
 * Q3 AUDIT fix: auth.setup.ts hilang dari repo (storage states .auth/*.json
 * gitignored → E2E tidak reproducible dari checkout fresh).
 *
 * Setup ini me-re-generate storageState untuk 5 role dari kredensial
 * E2eTestSeeder (database/seeders/E2eTestSeeder.php) via endpoint dev
 * GET /__e2e-login (token config services.e2e.login_token, default
 * 'local-apk-e2e', hanya aktif di env local/testing).
 *
 * Dipakai oleh project `setup` di playwright.config.js (testMatch
 * /auth\.setup\.ts/). Semua project lain punya `dependencies: ['setup']`
 * sehingga state selalu fresh sebelum suite role berjalan.
 */
import { test as setup, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const authDir = path.join(__dirname, '.auth');

const loginToken = process.env.E2E_LOGIN_TOKEN ?? 'local-apk-e2e';

// Pasangan email (E2eTestSeeder) → nama file storageState (playwright.config.js).
const roleAccounts = [
    { file: 'employee.json', email: 'employee@hrconnect.test' },
    { file: 'hr.json', email: 'hr@hrconnect.test' },
    { file: 'manager.json', email: 'manager@hrconnect.test' },
    { file: 'finance.json', email: 'finance@hrconnect.test' },
    { file: 'admin.json', email: 'admin@hrconnect.local' },
];

for (const { file, email } of roleAccounts) {
    setup(`authenticate ${file} (${email})`, async ({ page }) => {
        await page.goto(
            `/__e2e-login?token=${encodeURIComponent(loginToken)}&email=${encodeURIComponent(email)}&to=/home`,
        );

        // Login sukses = redirect keluar dari /login; body aplikasi muncul.
        await expect(page).not.toHaveURL(/\/login$/);
        await expect(page.locator('body')).toBeVisible();

        await page.context().storageState({ path: path.join(authDir, file) });
    });
}
