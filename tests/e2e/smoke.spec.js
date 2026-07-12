import { test, expect } from '@playwright/test';

/**
 * Simple smoke test to verify Playwright setup
 */
test.describe('Playwright Setup Verification', () => {
  test('should verify Playwright is working', async ({ page }) => {
    // Navigate to login page
    await page.goto('/login');
    
    // Basic assertion - page should load
    await expect(page).toHaveTitle(/HRConnect|Login/i);
  });

  test('should have basic HTML structure', async ({ page }) => {
    await page.goto('/login');
    
    // Check for basic elements
    const html = page.locator('html');
    await expect(html).toBeVisible();
  });
});
