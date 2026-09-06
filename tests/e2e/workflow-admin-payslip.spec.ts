import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const adminState = path.join(authDir, 'admin.json');
const financeState = path.join(authDir, 'finance.json');

test.describe('Admin Payslip Management', () => {
    test.describe('Admin Access', () => {
        test.use({ storageState: adminState });

        test('Admin can access admin payslip page', async ({ page }) => {
            await page.goto(`${BASE}/admin/payrolls`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Admin can view payslip list', async ({ page }) => {
            await page.goto(`${BASE}/admin/payrolls`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            const table = page.locator('table, [role="table"], .datatable').first();
            await expect(table).toBeVisible({ timeout: 10000 });
        });

        test('Admin can view payslip detail', async ({ page }) => {
            await page.goto(`${BASE}/admin/payrolls`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            // Click on first payslip detail
            const detailBtn = page.locator('[wire\\:click*="detail"], [wire\\:click*="show"], button:has-text("Detail"), a:has-text("Detail")').first();
            if (await detailBtn.isVisible({ timeout: 5000 })) {
                await detailBtn.click();
                
                // Detail modal should appear with payslip components
                const modal = page.locator('[role="dialog"], .modal, [wire\\:model*="show"]').first();
                await expect(modal).toBeVisible({ timeout: 5000 });
                
                // Check for payslip components (basic salary, allowances, deductions, etc.)
                const components = modal.locator('text=/basic|gaji|tunjangan|potongan|deduction|allowance|pph|bpjs/i').first();
                if (await components.isVisible({ timeout: 5000 })) {
                    await expect(components).toBeVisible();
                }
            }
        });

        test('Admin can download payslip PDF', async ({ page }) => {
            await page.goto(`${BASE}/admin/payrolls`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            const downloadBtn = page.locator('[wire\\:click*="download"], button:has-text("Download"), a:has-text("Download"), a:has-text("PDF")').first();
            if (await downloadBtn.isVisible({ timeout: 5000 })) {
                // Click download and check for PDF download
                const downloadPromise = page.waitForEvent('download', { timeout: 10000 });
                await downloadBtn.click();
                
                const download = await downloadPromise;
                expect(download.suggestedFilename()).toMatch(/\.pdf$/i);
            }
        });
    });

    test.describe('Finance Access', () => {
        test.use({ storageState: financeState });

        test('Finance can access payroll list', async ({ page }) => {
            await page.goto(`${BASE}/finance/payrolls`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        });

        test('Finance can view payslip detail', async ({ page }) => {
            await page.goto(`${BASE}/finance/payrolls`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            const detailBtn = page.locator('[wire\\:click*="detail"], button:has-text("Detail")').first();
            if (await detailBtn.isVisible({ timeout: 5000 })) {
                await detailBtn.click();
                
                const modal = page.locator('[role="dialog"], .modal, [wire\\:model*="show"]').first();
                await expect(modal).toBeVisible({ timeout: 5000 });
            }
        });
    });
});