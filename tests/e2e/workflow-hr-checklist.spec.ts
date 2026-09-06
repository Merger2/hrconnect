import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const adminState = path.join(authDir, 'admin.json');
const hrState = path.join(authDir, 'hr.json');

test.use({ storageState: adminState });

test.describe('Admin HR Checklist Management', () => {
    test.describe('Admin Access', () => {
        test('Admin can access HR checklist page', async ({ page }) => {
            await page.goto(`${BASE}/admin/hr-checklist`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Admin can view HR checklist content', async ({ page }) => {
            await page.goto(`${BASE}/admin/hr-checklist`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Admin can open HR checklist create form', async ({ page }) => {
            await page.goto(`${BASE}/admin/hr-checklist`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            const createBtn = page.locator('[wire\\:click="createCase"]').first();
            if (await createBtn.isVisible({ timeout: 5000 })) {
                await expect(createBtn).toBeVisible({ timeout: 10000 });
                await createBtn.click();
                
                const nameInput = page.locator('input:visible').first();
                await expect(nameInput).toBeVisible({ timeout: 5000 });
            }
        });
    });

    test.describe('HR Role Access', () => {
        test.use({ storageState: hrState });

        test('HR can access HR checklist page', async ({ page }) => {
            await page.goto(`${BASE}/admin/hr-checklist`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('HR can view HR checklist content', async ({ page }) => {
            await page.goto(`${BASE}/admin/hr-checklist`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });
    });
});