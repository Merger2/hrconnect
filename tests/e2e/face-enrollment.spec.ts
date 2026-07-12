import { test, expect } from '@playwright/test';

test.describe('Face Enrollment Flow', () => {
  test.beforeEach(async ({ page }) => {
    // Login sebagai HR Manager
    await page.goto('/login');
    await page.fill('input[name="email"]', 'hr@hrconnect.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test('E2E: Complete face enrollment', async ({ page }) => {
    // Navigate to employee face registration
    await page.goto('/admin/face-registration');
    
    // Check page loads
    await expect(page.locator('h1')).toContainText('Face Registration');
    
    // Start face capture
    await page.click('button:has-text("Start Enrollment")');
    
    // Wait for camera permission request (mock in test)
    await page.waitForSelector('video', { timeout: 5000 });
    
    // Verify camera is ready
    const videoElement = page.locator('video');
    await expect(videoElement).toBeVisible();
    
    // Simulate head-turn liveness (in real test, mock with video)
    await page.click('button:has-text("Capture Face")');
    
    // Wait for face detection result
    await page.waitForSelector('[data-testid="face-detected"]', { timeout: 10000 });
    
    // Verify face geometry descriptor captured
    const faceData = await page.locator('[data-testid="face-geometry"]').getAttribute('data-geometry');
    expect(faceData).toBeTruthy();
    
    // Confirm and save
    await page.click('button:has-text("Save Face")');
    
    // Verify success message
    await expect(page.locator('text=Face enrollment successful')).toBeVisible();
    
    // Verify photo_selfie was saved in DB
    const response = await page.request.get('/api/v1/employees/me');
    const employee = await response.json();
    expect(employee.data.photo_selfie).toBeTruthy();
    expect(employee.data.face_geometry).toBeTruthy();
  });

  test('E2E: Face enrollment with liveness check', async ({ page }) => {
    await page.goto('/admin/face-registration');
    
    // Start enrollment
    await page.click('button:has-text("Start Enrollment")');
    await page.waitForSelector('video');
    
    // Check guide overlay visible
    await expect(page.locator('[data-testid="face-guide-overlay"]')).toBeVisible();
    
    // Liveness: verify user can see head-turn prompt
    await expect(page.locator('text=Turn your head')).toBeVisible();
    
    // Simulate head movement
    await page.click('button:has-text("Capture Face")');
    
    // Wait for liveness verification
    await page.waitForSelector('[data-testid="liveness-verified"]', { timeout: 15000 });
    
    // Confirm save
    await page.click('button:has-text("Save Face")');
    
    // Success
    await expect(page.locator('text=Liveness check passed')).toBeVisible();
  });
});
