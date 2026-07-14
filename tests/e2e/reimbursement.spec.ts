import { test, expect } from '@playwright/test';

test.describe('Reimbursement Pages', () => {
  test('reimbursement index page loads', async ({ page }) => {
    await page.goto('/admin/reimbursements');
    await expect(page.locator('text=Kelola Reimbursement').first()).toBeVisible({ timeout: 10000 });
  });

  test('reimbursement index loads without server error', async ({ page }) => {
    const resp = await page.goto('/admin/reimbursements');
    expect(resp?.status()).toBeLessThan(500);
    await expect(page.locator('body')).not.toContainText('500');
  });

  test('employee reimbursement page loads', async ({ page }) => {
    await page.goto('/reimbursements');
    await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
  });

  test('apply reimbursement form page loads', async ({ page }) => {
    await page.goto('/reimbursements/apply');
    await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
  });

  test('employee reimbursement page loads without server error', async ({ page }) => {
    const resp = await page.goto('/reimbursements');
    expect(resp?.status()).toBeLessThan(500);
    await expect(page.locator('body')).not.toContainText('500');
  });

  test('apply reimbursement form loads without server error', async ({ page }) => {
    const resp = await page.goto('/reimbursements/apply');
    expect(resp?.status()).toBeLessThan(500);
    await expect(page.locator('body')).not.toContainText('500');
  });
});
