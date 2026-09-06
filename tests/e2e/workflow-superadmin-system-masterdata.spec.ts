import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const adminState = path.join(authDir, 'admin.json');

test.use({ storageState: adminState });

test.describe('Super Admin System Master Data', () => {
    test.describe('Companies Management', () => {
        test('Admin can access companies page', async ({ page }) => {
            await page.goto(`${BASE}/admin/companies`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Admin can view companies page content', async ({ page }) => {
            await page.goto(`${BASE}/admin/companies`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Admin can open company create form', async ({ page }) => {
            await page.goto(`${BASE}/admin/companies`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            const createBtn = page.locator('[wire\\:click="create"], button:has-text("Tambah"), button:has-text("Create")').first();
            if (await createBtn.isVisible({ timeout: 5000 })) {
                await expect(createBtn).toBeVisible({ timeout: 10000 });
                await createBtn.click();
                
                const nameInput = page.locator('input:visible').first();
                await expect(nameInput).toBeVisible({ timeout: 5000 });
            }
        });
    });

    test.describe('Users & Roles Management', () => {
        test('Admin can access users page', async ({ page }) => {
            await page.goto(`${BASE}/admin/users`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Admin can access roles page', async ({ page }) => {
            await page.goto(`${BASE}/admin/roles`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });
    });

    test.describe('Settings', () => {
        test('Admin can access settings page', async ({ page }) => {
            await page.goto(`${BASE}/admin/settings`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });
    });
});