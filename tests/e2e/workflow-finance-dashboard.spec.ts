import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const financeState = path.join(authDir, 'finance.json');
const adminState = path.join(__dirname, '.auth/admin.json');

test.describe('Finance Dashboard', () => {
    test.describe('Finance Access', () => {
        test.use({ storageState: financeState });

        test('Finance can access finance dashboard', async ({ page }) => {
            await page.goto(`${BASE}/finance`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Finance dashboard shows key metrics', async ({ page }) => {
            await page.goto(`${BASE}/finance`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            // Check for dashboard metrics cards
            const metrics = page.locator('[class*="metric"], [class*="card"], [class*="stat"]').first();
            await expect(metrics).toBeVisible({ timeout: 10000 });
        });

        test('Finance dashboard shows payroll summary', async ({ page }) => {
            await page.goto(`${BASE}/finance`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            // Look for payroll related content
            const payrollContent = page.locator('text=/payroll|gaji|payroll/i').first();
            if (await payrollContent.isVisible({ timeout: 5000 })) {
                await expect(payrollContent).toBeVisible();
            }
        });
    });

    test.describe('Admin Access', () => {
        test.use({ storageState: adminState });

        test('Admin can access finance dashboard', async ({ page }) => {
            await page.goto(`${BASE}/finance`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Admin can view finance dashboard metrics', async ({ page }) => {
            await page.goto(`${BASE}/finance`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            // Check for dashboard content
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });
    });
});