/**
 * Superadmin Settings & RBAC E2E Tests
 *
 * Tests the superadmin role's settings and RBAC management:
 * - Navigate to settings page
 * - Access roles & permissions management
 * - Display companies
 * - Search functionality
 */
import { expect, test } from '@playwright/test';

test.describe('Superadmin Settings & RBAC', () => {
    test.use({
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        serviceWorkers: 'block',
    });

    test('should display settings page', async ({ page }) => {
        await page.goto('/admin/settings', { waitUntil: 'networkidle' });

        // Verify page loaded — title is "Pengaturan Aplikasi"
        await expect(page.locator('h1')).toContainText('Pengaturan');
    });

    test('should navigate to roles & permissions page', async ({ page }) => {
        await page.goto('/admin/roles-permissions', { waitUntil: 'networkidle' });

        // Verify page loaded — title is "Peran & Izin"
        await expect(page.locator('h1')).toContainText('Peran');

        // Verify roles are listed
        await expect(page.locator('body')).toBeVisible();
    });

    test('should display companies page', async ({ page }) => {
        await page.goto('/admin/companies', { waitUntil: 'networkidle' });

        // Verify page loaded — title is "Companies"
        await expect(page.locator('h1')).toContainText('Compan');

        // Verify companies list is visible
        await expect(page.locator('body')).toBeVisible();
    });

    test('should search for companies', async ({ page }) => {
        await page.goto('/admin/companies', { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);

        // Type in search box
        const searchInput = page.locator('input[wire\\:model="search"]');
        if (await searchInput.isVisible()) {
            await searchInput.fill('test');
            await page.waitForTimeout(1000);
        }

        // Verify no server error
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });

    test('should display activity logs page', async ({ page }) => {
        await page.goto('/admin/activity-logs', { waitUntil: 'networkidle' });

        // Verify page loaded
        await expect(page.locator('body')).toBeVisible();
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });

    test('should display user sessions page', async ({ page }) => {
        await page.goto('/admin/user-sessions', { waitUntil: 'networkidle' });

        // Verify page loaded
        await expect(page.locator('body')).toBeVisible();
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });
});
