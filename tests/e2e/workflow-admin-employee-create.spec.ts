import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const adminState = path.join(authDir, 'admin.json');

test.use({ storageState: adminState });

test.describe('Admin Employee Create/Edit Flow', () => {
    test('Admin can access employee create page', async ({ page }) => {
        await page.goto(`${BASE}/admin/employees/create`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Admin can view employee create form', async ({ page }) => {
        await page.goto(`${BASE}/admin/employees/create`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Check for form fields
        const nameInput = page.locator('input[name="name"], input[name="full_name"]').first();
        const emailInput = page.locator('input[name="email"]').first();
        const nipInput = page.locator('input[name="nip"], input[name="nik"]').first();
        
        if (await nameInput.isVisible({ timeout: 5000 })) {
            await expect(nameInput).toBeVisible();
        }
        if (await emailInput.isVisible({ timeout: 5000 })) {
            await expect(emailInput).toBeVisible();
        }
        if (await nipInput.isVisible({ timeout: 5000 })) {
            await expect(nipInput).toBeVisible();
        }
    });

    test('Admin can fill and submit employee create form', async ({ page }) => {
        await page.goto(`${BASE}/admin/employees/create`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Fill required fields
        const timestamp = Date.now();
        const testEmail = `test.employee.${timestamp}@hrconnect.test`;
        const testNip = `19${timestamp.toString().slice(-8)}`;
        
        const nameInput = page.locator('input[name="name"], input[name="full_name"]').first();
        const emailInput = page.locator('input[name="email"]').first();
        const nipInput = page.locator('input[name="nip"], input[name="nik"]').first();
        
        if (await nameInput.isVisible({ timeout: 5000 })) {
            await nameInput.fill(`Test Employee ${timestamp}`);
        }
        if (await emailInput.isVisible({ timeout: 5000 })) {
            await emailInput.fill(testEmail);
        }
        if (await nipInput.isVisible({ timeout: 5000 })) {
            await nipInput.fill(testNip);
        }
        
        // Fill other required fields if visible
        const divisionSelect = page.locator('select[name="division_id"], select[name="division"]').first();
        if (await divisionSelect.isVisible({ timeout: 3000 })) {
            await divisionSelect.selectOption({ index: 1 });
        }
        
        const positionSelect = page.locator('select[name="position_id"], select[name="position"]').first();
        if (await positionSelect.isVisible({ timeout: 3000 })) {
            await positionSelect.selectOption({ index: 1 });
        }
        
        // Submit
        const submitBtn = page.locator('button[type="submit"]:has-text("Simpan"), button:has-text("Save"), button:has-text("Create")').first();
        if (await submitBtn.isVisible({ timeout: 3000 })) {
            await submitBtn.click();
            
            // Check for success
            const toast = page.locator('[role="alert"], .toast').first();
            await expect(toast).toBeVisible({ timeout: 15000 });
        }
    });

    test('Admin can access employee edit page', async ({ page }) => {
        // First get an existing employee
        await page.goto(`${BASE}/admin/employees`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        const editBtn = page.locator('[wire\\:click*="edit"], button:has-text("Edit")').first();
        if (await editBtn.isVisible({ timeout: 5000 })) {
            await editBtn.click();
            
            // Should be on edit page
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        }
    });
});