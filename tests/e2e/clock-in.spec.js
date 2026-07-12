import { test, expect } from '@playwright/test';

test.describe('Clock-in Flow with Face Recognition', () => {
  test.beforeEach(async ({ page, context }) => {
    // Grant permissions
    await context.grantPermissions(['camera', 'geolocation']);
    
    // Login
    await page.goto('/login');
    await page.locator('input[type="email"]').fill('employee@hrconnect.test');
    await page.locator('input[type="password"]').fill('password');
    await page.getByRole('button', { name: /login|masuk/i }).click();
    await page.waitForURL(/\/dashboard/, { timeout: 5000 });
  });

  test('should display clock-in page', async ({ page }) => {
    await page.goto('/attendance/clock-in');
    
    // Check for clock-in elements
    await expect(page.locator('text=/clock.*in|absen.*masuk/i')).toBeVisible();
  });

  test('should request camera and GPS permissions', async ({ page }) => {
    await page.goto('/attendance/clock-in');
    
    // Check if camera video is visible
    const video = page.locator('video').first();
    await expect(video).toBeVisible({ timeout: 5000 });
  });

  test('should show GPS location', async ({ page }) => {
    await page.goto('/attendance/clock-in');
    
    // Wait for GPS coordinates to be displayed (adjust selector)
    await expect(page.locator('text=/latitude|longitude|koordinat/i')).toBeVisible({ timeout: 8000 });
  });

  test('should verify face before clock-in', async ({ page }) => {
    await page.goto('/attendance/clock-in');
    
    // Wait for face verification
    const clockInButton = page.getByRole('button', { name: /clock.*in|absen.*masuk/i });
    
    // Button should be disabled initially
    await expect(clockInButton).toBeDisabled();
    
    // Wait for face verification to complete (adjust timeout as needed)
    // This will depend on your liveness detection implementation
    await expect(clockInButton).toBeEnabled({ timeout: 15000 });
  });

  test('should show success message after clock-in', async ({ page }) => {
    await page.goto('/attendance/clock-in');
    
    // Wait for face verification
    const clockInButton = page.getByRole('button', { name: /clock.*in|absen.*masuk/i });
    await expect(clockInButton).toBeEnabled({ timeout: 15000 });
    
    // Click clock-in
    await clockInButton.click();
    
    // Should show success toast/message
    await expect(page.locator('text=/success|berhasil/i')).toBeVisible({ timeout: 5000 });
  });
});
