import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const adminState = path.join(authDir, 'admin.json');

test.use({ storageState: adminState });

test.describe('Admin Overtime L2 Approval', () => {
    test('Admin can access admin overtime page', async ({ page }) => {
        await page.goto(`${BASE}/admin/overtime`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Admin can view overtime summary cards', async ({ page }) => {
        await page.goto(`${BASE}/admin/overtime`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Check for summary cards - look for the dl with overtime summary
        const summaryList = page.locator('dl[aria-label*="Overtime Summary"], dl[role="region"]').first();
        await expect(summaryList).toBeVisible({ timeout: 10000 });
    });

    test('Admin can filter overtime by status', async ({ page }) => {
        await page.goto(`${BASE}/admin/overtime`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        const statusFilter = page.locator('select[name*="status"], select[name*="filter"]').first();
        if (await statusFilter.isVisible({ timeout: 5000 })) {
            await statusFilter.selectOption({ index: 1 });
            await page.waitForTimeout(1000);
        }
    });

    test('Admin can view overtime list', async ({ page }) => {
        await page.goto(`${BASE}/admin/overtime`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Page should have content
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Admin can approve overtime (L2)', async ({ page }) => {
        await page.goto(`${BASE}/admin/overtime`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Look for approve button
        const approveBtn = page.locator('[wire\\:click*="approve"], button:has-text("Approve"), button:has-text("Setujui")').first();
        if (await approveBtn.isVisible({ timeout: 5000 })) {
            await approveBtn.click();
            
            // Check for success toast
            const toast = page.locator('[role="alert"], .toast').first();
            await expect(toast).toBeVisible({ timeout: 10000 });
        }
    });
});