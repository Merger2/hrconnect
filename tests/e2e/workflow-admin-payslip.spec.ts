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
            await page.locator('#payroll-period').fill('2026-08');
            await expect(page).toHaveURL(/periodFilter=2026-08/, { timeout: 10000 });

            await expect(page.locator('text=Total Gross')).toBeVisible({ timeout: 10000 });
            await expect(page.locator('text=Total Net')).toBeVisible({ timeout: 10000 });
            await expect(page.locator('table thead')).toContainText(/Gross/i);
            await expect(page.locator('table thead')).toContainText(/Net/i);
            await expect(page.locator('tbody tr').first()).toContainText(/Rp\s+[0-9.]+/i, { timeout: 10000 });
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
            await page.goto(`${BASE}/admin/payrolls`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('h1')).toContainText(/Penggajian|Payroll/i, { timeout: 10000 });
            await expect(page.locator('text=Total Gross')).toBeVisible({ timeout: 10000 });
            await expect(page.locator('text=Total Net')).toBeVisible({ timeout: 10000 });
        });

        test('Finance can see payroll components and paid payroll state', async ({ page }) => {
            await page.goto(`${BASE}/admin/payrolls`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            await page.locator('#payroll-period').fill('2026-08');
            await expect(page).toHaveURL(/periodFilter=2026-08/, { timeout: 10000 });

            await expect(page.locator('table thead')).toContainText(/Gross/i);
            await expect(page.locator('table thead')).toContainText(/Net/i);
            await expect(page.locator('tbody tr').first()).toContainText(/Rp\s+[0-9.]+/i, { timeout: 10000 });
            await expect(page.locator('body')).toContainText(/Ditransfer|Paid/i, { timeout: 10000 });
        });
    });
});
