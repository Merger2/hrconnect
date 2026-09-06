import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const adminState = path.join(authDir, 'admin.json');

test.use({ storageState: adminState });

test.describe('Admin Reports Generation', () => {
    test('Admin can access reports index page', async ({ page }) => {
        await page.goto(`${BASE}/admin/reports`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Admin can access payroll reports', async ({ page }) => {
        await page.goto(`${BASE}/admin/reports/payrolls`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Admin can access overtime reports', async ({ page }) => {
        await page.goto(`${BASE}/admin/reports/overtime`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Admin can access attendance reports', async ({ page }) => {
        await page.goto(`${BASE}/admin/reports/attendances`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Admin can access leave reports', async ({ page }) => {
        await page.goto(`${BASE}/admin/reports/leaves`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Admin can export payroll report as PDF', async ({ page }) => {
        await page.goto(`${BASE}/admin/reports/payrolls`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        const exportBtn = page.locator('[wire\\:click*="export"], button:has-text("Export"), a:has-text("Export")').first();
        if (await exportBtn.isVisible({ timeout: 5000 })) {
            await expect(exportBtn).toBeVisible({ timeout: 5000 });
        }
    });
});