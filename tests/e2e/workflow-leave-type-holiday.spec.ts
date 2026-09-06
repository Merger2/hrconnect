import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const adminState = path.join(authDir, 'admin.json');

test.use({ storageState: adminState });

test.describe('Admin Leave Types & Holidays', () => {
    test.describe('Leave Types Management', () => {
        test('Admin can access leave types page', async ({ page }) => {
            await page.goto(`${BASE}/admin/masterdata/leave-types`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Admin can view leave types list', async ({ page }) => {
            await page.goto(`${BASE}/admin/masterdata/leave-types`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            // Check for table/list
            const table = page.locator('table, [role="table"], .datatable').first();
            await expect(table).toBeVisible({ timeout: 10000 });
        });

        test('Admin can open leave type create form', async ({ page }) => {
            await page.goto(`${BASE}/admin/masterdata/leave-types`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            const createBtn = page.locator('[wire\\:click="create"], button:has-text("Tambah"), button:has-text("Create")').first();
            await expect(createBtn).toBeVisible({ timeout: 10000 });
            await createBtn.click();
            
            // Form should appear
            const nameInput = page.locator('input:visible').first();
            await expect(nameInput).toBeVisible({ timeout: 5000 });
        });
    });

    test.describe('Holidays Management', () => {
        test('Admin can access holidays page', async ({ page }) => {
            await page.goto(`${BASE}/admin/holidays`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Admin can view holidays list', async ({ page }) => {
            await page.goto(`${BASE}/admin/holidays`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            const table = page.locator('table, [role="table"], .datatable').first();
            await expect(table).toBeVisible({ timeout: 10000 });
        });

        test('Admin can open holiday create form', async ({ page }) => {
            await page.goto(`${BASE}/admin/holidays`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            const createBtn = page.locator('[wire\\:click="create"], button:has-text("Tambah"), button:has-text("Create")').first();
            await expect(createBtn).toBeVisible({ timeout: 10000 });
            await createBtn.click();
            
            const nameInput = page.locator('input:visible').first();
            await expect(nameInput).toBeVisible({ timeout: 5000 });
        });
    });
});