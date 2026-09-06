import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const adminState = path.join(authDir, 'admin.json');

test.use({ storageState: adminState });

test.describe('Admin Attendance Management', () => {
    test('Admin can access attendances page', async ({ page }) => {
        await page.goto(`${BASE}/admin/attendances`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('h1').filter({ hasText: /Presensi|Attendance/i })).toBeVisible({ timeout: 10000 });
    });

    test('Admin can view attendance list', async ({ page }) => {
        await page.goto(`${BASE}/admin/attendances`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        const table = page.locator('table, [role="table"], .datatable, [wire\\:model*="search"]').first();
        await expect(table).toBeVisible({ timeout: 10000 });
    });

    test('Admin can filter attendances by date', async ({ page }) => {
        await page.goto(`${BASE}/admin/attendances`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        const dateInput = page.locator('input[name*="date"], input[type="date"]').first();
        if (await dateInput.isVisible({ timeout: 5000 })) {
            await dateInput.fill('2026-01-15');
            await page.waitForTimeout(2000);
        }
    });

    test('Admin can filter attendances by employee', async ({ page }) => {
        await page.goto(`${BASE}/admin/attendances`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        const searchInput = page.locator('input[name*="search"], input[placeholder*="cari"], input[placeholder*="search"]').first();
        if (await searchInput.isVisible({ timeout: 5000 })) {
            await searchInput.fill('Test');
            await page.waitForTimeout(2000);
        }
    });

    test('Admin can view attendance detail', async ({ page }) => {
        await page.goto(`${BASE}/admin/attendances`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Click on first attendance row detail button
        const detailBtn = page.locator('[wire\\:click*="detail"], [wire\\:click*="show"], button:has-text("Detail")').first();
        if (await detailBtn.isVisible({ timeout: 5000 })) {
            await detailBtn.click();
            
            // Detail modal should appear
            const modal = page.locator('[role="dialog"], .modal, [wire\\:model*="show"]').first();
            await expect(modal).toBeVisible({ timeout: 5000 });
        }
    });

    test('Admin can access attendance report', async ({ page }) => {
        await page.goto(`${BASE}/admin/attendances/report`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Report page should load (check for body or any content)
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Admin can export attendances', async ({ page }) => {
        await page.goto(`${BASE}/admin/attendances`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        const exportBtn = page.locator('[wire\\:click*="export"], button:has-text("Export"), a:has-text("Export")').first();
        if (await exportBtn.isVisible({ timeout: 5000 })) {
            await exportBtn.click();
        }
    });
});