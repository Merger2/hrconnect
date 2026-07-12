import { test, expect } from '@playwright/test';

test.describe('Reimbursement Pages', () => {
  test('reimbursement index page loads', async ({ page }) => {
    await page.goto('/admin/reimbursements');
    await expect(page.locator('text=Kelola Reimbursement').first()).toBeVisible({ timeout: 10000 });
  });
});
