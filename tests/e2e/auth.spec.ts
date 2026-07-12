import { test, expect } from '@playwright/test';

test.describe('Authentication Flow', () => {
  test('should display login page with form elements', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('should login with valid credentials and reach dashboard', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill('employee@hrconnect.test');
    await page.locator('input[name="password"]').fill('password');
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard/, { timeout: 8000 });
  });

  test('should stay on login page with invalid credentials', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill('noone@example.com');
    await page.locator('input[name="password"]').fill('wrong');
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/login/, { timeout: 8000 });
  });
});
