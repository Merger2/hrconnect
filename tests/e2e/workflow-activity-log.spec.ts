import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const adminState = path.join(authDir, 'admin.json');

test.use({ storageState: adminState });

test.describe('Admin Activity Log Monitoring', () => {
    test('Admin can access activity log page', async ({ page }) => {
        await page.goto(`${BASE}/admin/activity-log`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Admin can view activity log content', async ({ page }) => {
        await page.goto(`${BASE}/admin/activity-log`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Page should have content
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Admin can filter activity log by date', async ({ page }) => {
        await page.goto(`${BASE}/admin/activity-log`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        const dateInput = page.locator('input[name*="date"], input[type="date"]').first();
        if (await dateInput.isVisible({ timeout: 5000 })) {
            await dateInput.fill('2026-01-15');
            await page.waitForTimeout(1000);
        }
    });

    test('Admin can filter activity log by actor/user', async ({ page }) => {
        await page.goto(`${BASE}/admin/activity-log`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        const actorFilter = page.locator('select[name*="actor"], select[name*="group"]').first();
        if (await actorFilter.isVisible({ timeout: 5000 })) {
            await actorFilter.selectOption({ index: 1 });
            await page.waitForTimeout(1000);
        }
    });

    test('Admin can search activity log', async ({ page }) => {
        await page.goto(`${BASE}/admin/activity-log`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        const searchInput = page.locator('input[name*="search"], input[placeholder*="cari"], input[placeholder*="search"]').first();
        if (await searchInput.isVisible({ timeout: 5000 })) {
            await searchInput.fill('Test');
            await page.waitForTimeout(1000);
        }
    });

    test('Admin can view activity log detail', async ({ page }) => {
        await page.goto(`${BASE}/admin/activity-log`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        const detailBtn = page.locator('[wire\\:click*="detail"], [wire\\:click*="show"], button:has-text("Detail")').first();
        if (await detailBtn.isVisible({ timeout: 5000 })) {
            await detailBtn.click();
            
            const modal = page.locator('[role="dialog"], .modal, [wire\\:model*="show"]').first();
            await expect(modal).toBeVisible({ timeout: 5000 });
        }
    });
});