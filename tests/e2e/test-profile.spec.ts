import { test, expect } from '@playwright/test';

test('manual profile check', async ({ page }) => {
  await page.goto('http://localhost:8000/login');
  await page.getByLabel('Alamat Email').fill('fikhahldiansyah28@gmail.com');
  await page.getByLabel('Kata Sandi').fill('ChangeMe!2026');
  await page.getByRole('button', { name: 'Masuk' }).click();
  await expect(page).not.toHaveURL(/.*\/login/);
  console.log('✓ Login OK, URL:', page.url());
  
  await page.goto('http://localhost:8000/user/profile');
  await page.waitForLoadState('networkidle');
  const userProfileText = await page.locator('body').textContent();
  console.log('User profile has "Profile":', userProfileText?.includes('Profile'));
  console.log('User profile text sample:', userProfileText?.substring(0, 300));
  
  await page.goto('http://localhost:8000/admin/profile');
  await page.waitForLoadState('networkidle');
  const adminProfileText = await page.locator('body').textContent();
  console.log('Admin profile has "Two Factor":', adminProfileText?.includes('Two Factor'));
  console.log('Admin profile has "Security":', adminProfileText?.includes('Security'));
  console.log('Admin profile text sample:', adminProfileText?.substring(0, 300));
});
