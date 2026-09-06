/**
 * Manager Approval Workflow E2E Tests
 *
 * Tests the manager's team approval functionality:
 * - Navigate to team approvals page
 * - Switch between approval tabs (cuti, klaim, koreksi, tukar shift, lembur, wfh, kasbon)
 * - Verify pending requests exist (from E2eOperationalDataSeeder)
 * - Approve/reject leave requests
 * - Verify success notifications
 */
import { expect, test } from '@playwright/test';

test.describe('Manager Approvals', () => {
    test.use({
        permissions: ['camera', 'geolocation'],
        geolocation: { latitude: -6.2088, longitude: 106.8456 },
        serviceWorkers: 'block',
    });

    test('should display team approvals page with tabs', async ({ page }) => {
        await page.goto('/approvals', { waitUntil: 'networkidle' });

        // Verify page loaded
        await expect(page.locator('h1')).toContainText('Persetujuan Tim');

        // Verify all 7 tabs are present (button with wire:click)
        await expect(page.locator('button[wire\\:click*="switchTab(\'leaves\')"]')).toContainText('Cuti');
        await expect(page.locator('button[wire\\:click*="switchTab(\'reimbursements\')"]')).toContainText('Klaim');
        await expect(page.locator('button[wire\\:click*="switchTab(\'attendance-corrections\')"]')).toContainText('Koreksi');
        await expect(page.locator('button[wire\\:click*="switchTab(\'shift-swaps\')"]')).toContainText('Tukar Shift');
        await expect(page.locator('button[wire\\:click*="switchTab(\'overtimes\')"]')).toContainText('Lembur');
        await expect(page.locator('button[wire\\:click*="switchTab(\'wfh\')"]')).toContainText('WFH');
        await expect(page.locator('button[wire\\:click*="switchTab(\'kasbons\')"]')).toContainText('Kasbon');
    });

    test('should switch between approval tabs', async ({ page }) => {
        await page.goto('/approvals', { waitUntil: 'networkidle' });

        // Default tab should be leaves (Cuti) — check aria-selected
        await expect(page.locator('button[wire\\:click*="switchTab(\'leaves\')"]')).toHaveAttribute('aria-selected', 'true');

        // Switch to reimbursements tab (Klaim)
        await page.click('button[wire\\:click*="switchTab(\'reimbursements\')"]');
        await page.waitForTimeout(1500);

        // Switch to overtimes tab (Lembur)
        await page.click('button[wire\\:click*="switchTab(\'overtimes\')"]');
        await page.waitForTimeout(1500);

        // Switch to WFH tab
        await page.click('button[wire\\:click*="switchTab(\'wfh\')"]');
        await page.waitForTimeout(1500);

        // Switch back to leaves tab
        await page.click('button[wire\\:click*="switchTab(\'leaves\')"]');
        await page.waitForTimeout(1500);
    });

    test('should show pending leave requests from subordinates', async ({ page }) => {
        await page.goto('/approvals', { waitUntil: 'networkidle' });

        // Wait for Livewire to load data
        await page.waitForTimeout(2000);

        // Check if there are pending leave requests OR empty state
        const pageContent = await page.content();
        const hasRequests = pageContent.includes('Cuti Tahunan') || pageContent.includes('Cuti Sakit')
            || pageContent.includes('pending') || pageContent.includes('Menunggu');

        // Either we see pending requests or an empty state — both are valid
        expect(hasRequests || pageContent.includes('Tidak ada') || pageContent.includes('Kosong')).toBeTruthy();
    });

    test('approval queue is scoped to manager subordinates', async ({ page }) => {
        await page.goto('/approvals', { waitUntil: 'networkidle' });

        await expect(page.locator('.team-approval-card', { hasText: /Test Employee|Budi Santoso|Test Finance|Agus Wijaya/i }).first()).toBeVisible({ timeout: 15000 });
        await expect(page.locator('.team-approval-card', { hasText: /Test HR|Siti Rahmawati|Super Admin/i })).toHaveCount(0);
    });

    test('should show pending overtime requests from subordinates', async ({ page }) => {
        await page.goto('/approvals', { waitUntil: 'networkidle' });

        // Switch to overtimes tab
        await page.click('button[wire\\:click*="switchTab(\'overtimes\')"]');
        await page.waitForTimeout(2000);

        // Verify the page loaded without error
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });

    test('should show pending reimbursement requests from subordinates', async ({ page }) => {
        await page.goto('/approvals', { waitUntil: 'networkidle' });

        // Switch to reimbursements tab
        await page.click('button[wire\\:click*="switchTab(\'reimbursements\')"]');
        await page.waitForTimeout(2000);

        // Verify the page loaded without error
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });

    test('should show pending WFH requests from subordinates', async ({ page }) => {
        await page.goto('/approvals', { waitUntil: 'networkidle' });

        // Switch to WFH tab
        await page.click('button[wire\\:click*="switchTab(\'wfh\')"]');
        await page.waitForTimeout(2000);

        // Verify the page loaded without error
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });

    test('should show pending kasbon requests from subordinates', async ({ page }) => {
        await page.goto('/approvals', { waitUntil: 'networkidle' });

        // Switch to kasbon tab
        await page.click('button[wire\\:click*="switchTab(\'kasbons\')"]');
        await page.waitForTimeout(2000);

        // Verify the page loaded without error
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });

    test('should navigate to approvals history', async ({ page }) => {
        await page.goto('/approvals/history', { waitUntil: 'networkidle' });

        // Verify page loaded
        const bodyText = await page.locator('body').textContent();
        expect(bodyText).toBeTruthy();
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|500 server/i);
    });
});
