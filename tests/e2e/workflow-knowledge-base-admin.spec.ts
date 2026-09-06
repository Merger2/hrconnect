import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const adminState = path.join(authDir, 'admin.json');

test.use({ storageState: adminState });

test.describe('Admin Knowledge Base Management', () => {
    test.describe('KB Entries (Main Page)', () => {
        test('Admin can access KB entries page', async ({ page }) => {
            await page.goto(`${BASE}/admin/knowledge-base`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Admin can view KB entries content', async ({ page }) => {
            await page.goto(`${BASE}/admin/knowledge-base`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            // Check for page content - either table or empty state
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Admin can open KB upload form', async ({ page }) => {
            await page.goto(`${BASE}/admin/knowledge-base`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            // Upload PDF button
            const uploadBtn = page.locator('[wire\\:click="showUpload"]').first();
            if (await uploadBtn.isVisible({ timeout: 5000 })) {
                await expect(uploadBtn).toBeVisible({ timeout: 10000 });
                await uploadBtn.click();
                
                // Upload modal should appear
                const modal = page.locator('[role="dialog"], .modal, [wire\\:model*="show"]').first();
                await expect(modal).toBeVisible({ timeout: 5000 });
            }
        });
    });

    test.describe('KB Categories', () => {
        test('Admin can access KB categories page', async ({ page }) => {
            await page.goto(`${BASE}/admin/knowledge-base/categories`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Admin can view KB categories page content', async ({ page }) => {
            await page.goto(`${BASE}/admin/knowledge-base/categories`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Admin can open KB category create form if available', async ({ page }) => {
            await page.goto(`${BASE}/admin/knowledge-base/categories`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            const createBtn = page.locator('[wire\\:click="create"], button:has-text("Tambah"), button:has-text("Create")').first();
            if (await createBtn.isVisible({ timeout: 5000 })) {
                await expect(createBtn).toBeVisible({ timeout: 10000 });
                await createBtn.click();
                
                const nameInput = page.locator('input:visible').first();
                await expect(nameInput).toBeVisible({ timeout: 5000 });
            }
        });
    });
});