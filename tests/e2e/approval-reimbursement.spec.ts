import { test, expect } from '@playwright/test';

test.describe('Reimbursement Admin Pages', () => {
  test('reimbursement admin index page loads', async ({ page }) => {
    await page.goto('/admin/reimbursements');
    await expect(page.locator('text=Kelola Reimbursement').first()).toBeVisible({ timeout: 10000 });
  });

  test('reimbursement admin index loads without server error', async ({ page }) => {
    const resp = await page.goto('/admin/reimbursements');
    expect(resp?.status()).toBeLessThan(500);
    await expect(page.locator('body')).not.toContainText('500');
  });
});
