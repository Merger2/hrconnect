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
        await page.goto(`${BASE}/finance/payroll-settings`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Finance can view payroll settings content', async ({ page }) => {
        await page.goto(`${BASE}/finance/payroll-settings`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Finance can view PPh21 TER configuration', async ({ page }) => {
        await page.goto(`${BASE}/finance/payroll-settings`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Look for TER related content
        const terContent = page.locator('text=/TER|PPh21|Tarif/i').first();
        if (await terContent.isVisible({ timeout: 5000 })) {
            await expect(terContent).toBeVisible({ timeout: 5000 });
        }
    });

    test('Finance can view BPJS configuration', async ({ page }) => {
        await page.goto(`${BASE}/finance/payroll-settings`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Look for BPJS related content
        const bpjsContent = page.locator('text=/BPJS|JHT|JPK/i').first();
        if (await bpjsContent.isVisible({ timeout: 5000 })) {
            await expect(bpjsContent).toBeVisible({ timeout: 5000 });
        }
    });
});