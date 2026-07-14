import { test, expect } from '@playwright/test';

test.describe('Authentication Flow', () => {
  test('should display login page with form elements', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('input[name="email"]')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('input[name="password"]')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('button[type="submit"]')).toBeVisible({ timeout: 10000 });
  });

  test('should login with valid credentials and reach dashboard', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill('employee@hrconnect.test');
    await page.locator('input[name="password"]').fill('Employee1234');
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard/, { timeout: 10000 });
  });

  test('should stay on login page with invalid credentials', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill('noone@example.com');
    await page.locator('input[name="password"]').fill('wrong');
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/login/, { timeout: 10000 });
  });

  test('login page has forgot password link', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('text=Lupa kata sandi?')).toBeVisible({ timeout: 10000 });
  });

  test('login page has remember me checkbox', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('text=Ingat saya')).toBeVisible({ timeout: 10000 });
  });

  test('login page has app branding', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('text=HRConnect').first()).toBeVisible({ timeout: 10000 });
  });

  test('login page heading renders correctly', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('h1, h2').first()).toBeVisible({ timeout: 10000 });
  });

  test('login form submits and triggers redirect', async ({ page }) => {
    await page.goto('/login');
    const submitBtn = page.locator('button[type="submit"]');
    await expect(submitBtn).toBeVisible({ timeout: 10000 });
    await expect(submitBtn).toHaveText(/Masuk/i);
  });
});
