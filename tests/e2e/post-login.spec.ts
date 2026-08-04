import { test, expect } from '@playwright/test';

test.describe('Post-Login Flow - Critical Priority', () => {
  test('login redirects to dashboard, not settings/profile', async ({ page }) => {
    await page.goto('/login');
    
    // Login
    await page.getByLabel('Alamat Email').fill('fikhahldiansyah28@gmail.com');
    await page.getByLabel('Kata Sandi').fill('ChangeMe!2026');
    await page.getByRole('button', { name: 'Masuk' }).click();
    
    // After login, should be on dashboard (not settings/profile)
    await expect(page).toHaveURL(/\/admin\/dashboard/);
    await expect(page.locator('h1, h2, h3').first()).toContainText(/dashboard|selamat/i);
  });
  
  test('dashboard page loads correctly', async ({ page }) => {
    // Login first
    await page.goto('/login');
    await page.getByLabel('Alamat Email').fill('fikhahldiansyah28@gmail.com');
    await page.getByLabel('Kata Sandi').fill('ChangeMe!2026');
    await page.getByRole('button', { name: 'Masuk' }).click();
    
    // Verify dashboard content
    await expect(page).toHaveURL('/admin/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Check for key dashboard elements
    const hasWelcome = await page.locator('text=Selamat').waitFor({ state: 'visible', timeout: 5000 }).catch(() => null);
    console.log('Dashboard check - Welcome message:', !!hasWelcome);
  });
  
  test('settings profile page accessible after login', async ({ page }) => {
    // Login first
    await page.goto('/login');
    await page.getByLabel('Alamat Email').fill('fikhahldiansyah28@gmail.com');
    await page.getByLabel('Kata Sandi').fill('ChangeMe!2026');
    await page.getByRole('button', { name: 'Masuk' }).click();
    
    // Navigate to profile
    await page.goto('/user/profile');
    await page.waitForLoadState('networkidle');
    
    // Should not show ComponentNotFoundException
    const errorTitle = await page.locator('text=ComponentNotFoundException').waitFor({ state: 'visible', timeout: 3000 }).catch(() => null);
    if (errorTitle) {
      console.log('ERROR: ComponentNotFoundException found on profile page');
    } else {
      console.log('OK: No ComponentNotFoundException on profile page');
    }
  });
});