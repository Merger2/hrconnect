import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const financeState = path.join(authDir, 'finance.json');

test.use({ storageState: financeState });

test.describe('Finance Payroll Configuration', () => {
    test('Finance can access payroll settings page', async ({ page }) => {
        await page.goto(`${BASE}/admin/payrolls/settings`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toContainText(/Payroll|Penggajian|Komponen|Pajak|BPJS/i, { timeout: 10000 });
    });

    test('Finance can view payroll settings content', async ({ page }) => {
        await page.goto(`${BASE}/admin/payrolls/settings`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toContainText(/Komponen|Component|Pajak|Tax|BPJS/i, { timeout: 10000 });
    });

    test('Finance can view PPh21 TER configuration', async ({ page }) => {
        await page.goto(`${BASE}/admin/payrolls/settings`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toContainText(/TER|PPh\s*21|PPh21|Tarif/i, { timeout: 10000 });
    });

    test('Finance can view BPJS configuration', async ({ page }) => {
        await page.goto(`${BASE}/admin/payrolls/settings`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toContainText(/BPJS|JHT|JPK|Kesehatan|Ketenagakerjaan/i, { timeout: 10000 });
    });
});
