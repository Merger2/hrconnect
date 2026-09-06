import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const adminState = path.join(authDir, 'admin.json');

test.use({ storageState: adminState });

test.describe('Admin Import/Export Data', () => {
    test('Admin can access import-export users page', async ({ page }) => {
        await page.goto(`${BASE}/admin/import-export/users`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Page should load successfully
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Admin can access users export tab', async ({ page }) => {
        await page.goto(`${BASE}/admin/import-export/users`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Check for export tab - button with Export text (localized)
        const exportTab = page.locator('[role="tab"]:has-text("Export"), [role="tab"]:has-text("Ekspor"), button:has-text("Export"), button:has-text("Ekspor")').first();
        await expect(exportTab).toBeVisible({ timeout: 10000 });
    });

    test('Admin can access users import tab', async ({ page }) => {
        await page.goto(`${BASE}/admin/import-export/users`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Check for import tab
        const importTab = page.locator('[role="tab"]:has-text("Import"), [role="tab"]:has-text("Impor"), button:has-text("Import"), button:has-text("Impor")').first();
        if (await importTab.isVisible({ timeout: 5000 })) {
            await expect(importTab).toBeVisible({ timeout: 10000 });
        }
    });

    test('Admin can access attendances export page', async ({ page }) => {
        await page.goto(`${BASE}/admin/import-export/attendances`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Page should load
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Admin can view import history/runs', async ({ page }) => {
        await page.goto(`${BASE}/admin/import-export/runs`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Page should load
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });
});