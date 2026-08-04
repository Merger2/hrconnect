import { test, expect } from '@playwright/test';

const email = 'fikhahldiansyah28@gmail.com';
const password = 'ChangeMe!2026';

async function loginReal(page: import('@playwright/test').Page) {
  await page.goto('/login');
  await page.waitForLoadState('domcontentloaded');
  await page.getByLabel('Alamat Email').fill(email);
  await page.getByLabel('Kata Sandi').fill(password);
  await page.getByRole('button', { name: 'Masuk' }).click();

  await page.waitForURL(url => !url.toString().includes('/login'), { timeout: 15000 });
  await expect(page).not.toHaveURL(/.*\/login/);
}

test.describe('Admin Profile & Security Panel (Super Admin)', () => {
  test.beforeEach(async ({ page }) => {
    await loginReal(page);
  });

  test('admin profile page loads', async ({ page }) => {
    await page.goto('/admin/profile');

    // Wait for page to load
    await page.waitForLoadState('domcontentloaded');

    // Check URL
    await expect(page).toHaveURL(/.*\/admin\/profile/);
    
    // No server error
    await expect(page.locator('body')).not.toContainText(/server error|exception|stack trace/i);
    
    // Page title should be visible (Admin Profile is in the page shell title)
    await expect(page.locator('h1')).toContainText('Admin Profile');
  });

  test('admin profile shows identity info', async ({ page }) => {
    await page.goto('/admin/profile');
    await page.waitForLoadState('domcontentloaded');
    
    // Email should be visible in the toolbar area
    await expect(page.getByText(email)).toBeVisible();
    
    // Super Admin badge should be visible
    await expect(page.getByText('Super Admin')).toBeVisible();
  });

  test('security panel opens', async ({ page }) => {
    await page.goto('/admin/profile#security');
    await page.waitForLoadState('domcontentloaded');
    
    // Click security tab in sidebar - try matching by text directly
    const securityTab = page.getByRole('button', { name: /Security/i });
    await securityTab.waitFor({ state: 'visible', timeout: 10000 });
    await securityTab.click();
    
    // Two Factor Authentication section should appear
    await expect(page.locator('text=Two Factor Authentication')).toBeVisible({ timeout: 15000 });
  });

  test('face ID section shows', async ({ page }) => {
    await page.goto('/admin/profile');
    await page.waitForLoadState('domcontentloaded');
    
    // Face recognition section - check for any face-related text
    await expect(page.locator('body')).toContainText(/Face/i);
  });
});