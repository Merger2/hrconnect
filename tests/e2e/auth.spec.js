import { test, expect } from '@playwright/test';

test.describe('Authentication Flow', () => {
  test('should display login page', async ({ page }) => {
    await page.goto('/login');
    
    // Check for login form elements
    await expect(page.locator('input[type="email"]')).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();
    await expect(page.getByRole('button', { name: /login|masuk/i })).toBeVisible();
  });

  test('should show validation error for empty credentials', async ({ page }) => {
    await page.goto('/login');
    
    // Click login without filling form
    await page.getByRole('button', { name: /login|masuk/i }).click();
    
    // Should show validation errors
    await expect(page.locator('text=/email.*required/i')).toBeVisible({ timeout: 3000 });
  });

  test('should login with valid credentials', async ({ page }) => {
    await page.goto('/login');
    
    // Fill in credentials (adjust based on your test user)
    await page.locator('input[type="email"]').fill('test@hrconnect.test');
    await page.locator('input[type="password"]').fill('password');
    
    // Click login
    await page.getByRole('button', { name: /login|masuk/i }).click();
    
    // Should redirect to dashboard
    await expect(page).toHaveURL(/\/dashboard/, { timeout: 5000 });
  });
});
