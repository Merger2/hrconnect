import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const adminState = path.join(authDir, 'admin.json');

test.use({ storageState: adminState });

test.describe('Admin Master Data - Divisi, Jabatan, Shift', () => {
    test.describe('Divisi Management', () => {
        test('Admin can access divisi page', async ({ page }) => {
            await page.goto(`${BASE}/admin/masterdata/division`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('h1').filter({ hasText: /Divisi|Division/i })).toBeVisible({ timeout: 10000 });
        });

        test('Admin can view divisi list', async ({ page }) => {
            await page.goto(`${BASE}/admin/masterdata/division`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            // Table or list should be visible
            const table = page.locator('table, [role="table"], .datatable, [wire\\:model*="search"]').first();
            await expect(table).toBeVisible({ timeout: 10000 });
        });

        test('Admin can open divisi create form', async ({ page }) => {
            await page.goto(`${BASE}/admin/masterdata/division`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            // Click create button
            const createBtn = page.locator('[wire\\:click="create"], button:has-text("Tambah"), button:has-text("Create")').first();
            await expect(createBtn).toBeVisible({ timeout: 10000 });
            await createBtn.click();
            
            // Form should appear
            const codeInput = page.locator('input:visible').first();
            await expect(codeInput).toBeVisible({ timeout: 5000 });
        });
    });

    test.describe('Jabatan Management', () => {
        test('Admin can access jabatan page', async ({ page }) => {
            await page.goto(`${BASE}/admin/masterdata/job-title`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('h1').filter({ hasText: /Jabatan|Job Title/i })).toBeVisible({ timeout: 10000 });
        });

        test('Admin can view jabatan list', async ({ page }) => {
            await page.goto(`${BASE}/admin/masterdata/job-title`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            const table = page.locator('table, [role="table"], .datatable, [wire\\:model*="search"]').first();
            await expect(table).toBeVisible({ timeout: 10000 });
        });

        test('Admin can open jabatan create form', async ({ page }) => {
            await page.goto(`${BASE}/admin/masterdata/job-title`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            const createBtn = page.locator('[wire\\:click="create"], button:has-text("Tambah"), button:has-text("Create")').first();
            await expect(createBtn).toBeVisible({ timeout: 10000 });
            await createBtn.click();
            
            const nameInput = page.locator('input:visible').first();
            await expect(nameInput).toBeVisible({ timeout: 5000 });
        });
    });

    test.describe('Shift Management', () => {
        test('Admin can access shift page', async ({ page }) => {
            await page.goto(`${BASE}/admin/masterdata/shift`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            await expect(page.locator('h1').filter({ hasText: /Shift/i })).toBeVisible({ timeout: 10000 });
        });

        test('Admin can view shift list', async ({ page }) => {
            await page.goto(`${BASE}/admin/masterdata/shift`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            const table = page.locator('table, [role="table"], .datatable, [wire\\:model*="search"]').first();
            await expect(table).toBeVisible({ timeout: 10000 });
        });

        test('Admin can open shift create form', async ({ page }) => {
            await page.goto(`${BASE}/admin/masterdata/shift`, { waitUntil: 'domcontentloaded', timeout: 20000 });
            
            const createBtn = page.locator('[wire\\:click="create"], button:has-text("Tambah"), button:has-text("Create")').first();
            await expect(createBtn).toBeVisible({ timeout: 10000 });
            await createBtn.click();
            
            const nameInput = page.locator('input:visible').first();
            await expect(nameInput).toBeVisible({ timeout: 5000 });
        });
    });
});