import { test, expect } from '@playwright/test';

test.describe('Face Enrollment Flow', () => {
  test.beforeEach(async ({ page }) => {
    // Login first (adjust credentials as needed)
    await page.goto('/login');
    await page.locator('input[type="email"]').fill('employee@hrconnect.test');
    await page.locator('input[type="password"]').fill('password');
    await page.getByRole('button', { name: /login|masuk/i }).click();
    await page.waitForURL(/\/dashboard/, { timeout: 5000 });
  });

  test('should display face enrollment page', async ({ page }) => {
    // Navigate to face enrollment (adjust route based on your app)
    await page.goto('/profile/face-enrollment');
    
    // Check for camera permission request elements
    await expect(page.locator('video, canvas')).toBeVisible({ timeout: 3000 });
  });

  test('should initialize face-api.js', async ({ page }) => {
    await page.goto('/profile/face-enrollment');
    
    // Wait for face-api to load
    const faceApiLoaded = await page.evaluate(() => {
      return typeof window.faceapi !== 'undefined';
    });
    
    expect(faceApiLoaded).toBeTruthy();
  });

  test('should detect face in camera feed', async ({ page, context }) => {
    // Grant camera permission
    await context.grantPermissions(['camera']);
    
    await page.goto('/profile/face-enrollment');
    
    // Wait for video element
    const video = page.locator('video').first();
    await expect(video).toBeVisible();
    
    // Wait for face detection (this might take a few seconds)
    // You'll need to adjust this based on your UI feedback
    await expect(page.locator('text=/face.*detected/i')).toBeVisible({ timeout: 10000 });
  });

  test('should show liveness detection progress', async ({ page, context }) => {
    await context.grantPermissions(['camera']);
    
    await page.goto('/profile/face-enrollment');
    
    // Check for liveness indicators (adjust selectors based on your UI)
    await expect(page.locator('text=/turn.*head|look.*left|look.*right/i')).toBeVisible({ timeout: 5000 });
  });
});
