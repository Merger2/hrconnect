import { test, expect } from '@playwright/test';

test.describe('Clock-In Flow with GPS Geofencing', () => {
  test.beforeEach(async ({ page, context }) => {
    // Mock geolocation
    await context.grantPermissions(['geolocation']);
    
    // Set mock location (office coordinates)
    await page.goto('/login');
    await page.fill('input[name="email"]', 'employee@hrconnect.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test('E2E: Clock-in with GPS verification', async ({ page }) => {
    // Navigate to attendance/clock-in
    await page.goto('/attendance/clock-in');
    
    // Verify page loads
    await expect(page.locator('h1')).toContainText('Clock In');
    
    // Check GPS location picker visible
    await expect(page.locator('[data-testid="location-map"]')).toBeVisible();
    
    // Verify current location on map
    const mapContainer = page.locator('[data-testid="location-map"]');
    await expect(mapContainer).toBeVisible();
    
    // Wait for geofence validation
    await page.waitForSelector('[data-testid="geofence-status"]', { timeout: 5000 });
    
    // Check if within geofence
    const geofenceStatus = await page.locator('[data-testid="geofence-status"]').textContent();
    expect(geofenceStatus).toContain('Within');
    
    // Start face verification
    await page.click('button:has-text("Verify Face")');
    await page.waitForSelector('video', { timeout: 5000 });
    
    // Capture face
    await page.click('button:has-text("Capture")');
    await page.waitForSelector('[data-testid="face-verified"]', { timeout: 10000 });
    
    // Clock in
    await page.click('button:has-text("Clock In")');
    
    // Verify success
    await expect(page.locator('text=Successfully clocked in')).toBeVisible();
    
    // Check API: attendance record created
    const response = await page.request.get('/api/v1/attendances/today');
    const attendance = await response.json();
    expect(attendance.data).toBeTruthy();
    expect(attendance.data.check_in_time).toBeTruthy();
    expect(attendance.data.latitude).toBeTruthy();
    expect(attendance.data.longitude).toBeTruthy();
    expect(attendance.data.is_face_verified).toBe(true);
  });

  test('E2E: Clock-in outside geofence should fail', async ({ page, context }) => {
    // Set location OUTSIDE geofence (different coordinates)
    await context.setGeolocation({ latitude: -6.5, longitude: 106.7 });
    
    await page.goto('/attendance/clock-in');
    
    // Wait for geofence check
    await page.waitForSelector('[data-testid="geofence-status"]', { timeout: 5000 });
    
    // Should show error
    await expect(page.locator('text=Outside office geofence')).toBeVisible();
    
    // Button should be disabled
    const clockBtn = page.locator('button:has-text("Verify Face")');
    await expect(clockBtn).toBeDisabled();
  });

  test('E2E: Face verification failure blocks clock-in', async ({ page }) => {
    await page.goto('/attendance/clock-in');
    
    // Location valid
    await page.waitForSelector('[data-testid="geofence-status"]');
    await expect(page.locator('[data-testid="geofence-status"]')).toContainText('Within');
    
    // Try to clock in without face
    await page.click('button:has-text("Clock In")');
    
    // Should show error
    await expect(page.locator('text=Face verification required')).toBeVisible();
    
    // Start verification
    await page.click('button:has-text("Verify Face")');
    await page.waitForSelector('video');
    
    // Capture without valid face
    await page.click('button:has-text("Capture")');
    
    // Wait for error
    await page.waitForSelector('[data-testid="face-error"]', { timeout: 10000 });
    await expect(page.locator('text=Face not detected')).toBeVisible();
  });

  test('E2E: Clock-out records location correctly', async ({ page }) => {
    // Clock in first
    await page.goto('/attendance/clock-in');
    await page.click('button:has-text("Verify Face")');
    await page.waitForSelector('[data-testid="face-verified"]', { timeout: 10000 });
    await page.click('button:has-text("Clock In")');
    await page.waitForSelector('text=Successfully clocked in');
    
    // Navigate to clock-out
    await page.goto('/attendance/clock-out');
    await expect(page.locator('h1')).toContainText('Clock Out');
    
    // Verify face and clock out
    await page.click('button:has-text("Verify Face")');
    await page.waitForSelector('[data-testid="face-verified"]', { timeout: 10000 });
    await page.click('button:has-text("Clock Out")');
    
    // Success
    await expect(page.locator('text=Successfully clocked out')).toBeVisible();
    
    // Check API: attendance updated
    const response = await page.request.get('/api/v1/attendances/today');
    const attendance = await response.json();
    expect(attendance.data.check_out_time).toBeTruthy();
  });
});
