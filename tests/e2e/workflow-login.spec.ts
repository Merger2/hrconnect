import { test, expect } from '@playwright/test';

const BASE = process.env.E2E_BASE_URL || 'http://localhost:5173';

test.describe('workflow: employee login', () => {
    test('employee can login with valid credentials', async ({ page }) => {
        await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await page.locator('input[name="email"]').fill('employee@hrconnect.test');
        await page.locator('input[name="password"]').fill('password');
        await page.locator('button[type="submit"]').click();
        
        await expect(page).not.toHaveURL(/\/login$/);
        await expect(page.locator('h1').filter({ hasText: /Dashboard|Home/i })).toBeVisible({ timeout: 10000 });
    });

    test('login shows error for invalid email', async ({ page }) => {
        await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await page.locator('input[name="email"]').fill('invalid@test.com');
        await page.locator('input[name="password"]').fill('password');
        await page.locator('button[type="submit"]').click();
        
        await expect(page.locator('[role="alert"]').filter({ hasText: /email|invalid/i })).toBeVisible({ timeout: 5000 });
    });

    test('login shows error for wrong password', async ({ page }) => {
        await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await page.locator('input[name="email"]').fill('employee@hrconnect.test');
        await page.locator('input[name="password"]').fill('wrongpassword');
        await page.locator('button[type="submit"]').click();
        
        await expect(page.locator('[role="alert"]').filter({ hasText: /password|invalid/i })).toBeVisible({ timeout: 5000 });
    });

    test('login redirects to dashboard after success', async ({ page }) => {
        await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await page.locator('input[name="email"]').fill('employee@hrconnect.test');
        await page.locator('input[name="password"]').fill('password');
        await page.locator('button[type="submit"]').click();
        
        await expect(page).toHaveURL(/\/(home|dashboard)/i, { timeout: 15000 });
    });

    test('session persists after login', async ({ page }) => {
        await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await page.locator('input[name="email"]').fill('employee@hrconnect.test');
        await page.locator('input[name="password"]').fill('password');
        await page.locator('button[type="submit"]').click();
        await expect(page.locator('h1').filter({ hasText: /Dashboard|Home/i })).toBeVisible({ timeout: 10000 });
        
        await page.goto(`${BASE}/home`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('h1').filter({ hasText: /Dashboard|Home/i })).toBeVisible();
    });
});
