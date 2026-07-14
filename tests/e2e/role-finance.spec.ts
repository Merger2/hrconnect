import { test, expect } from '@playwright/test';

test.describe('Role: Finance — all accessible pages', () => {
  test.use({ storageState: 'tests/e2e/.auth/finance.json' });

  test('/dashboard loads successfully', async ({ page }) => {
    const resp = await page.goto('/dashboard');
    expect(resp?.status()).toBeLessThan(400);
  });

  test('/loans loads successfully', async ({ page }) => {
    const resp = await page.goto('/loans');
    expect(resp?.status()).toBeLessThan(400);
  });

  test('/payroll loads successfully', async ({ page }) => {
    const resp = await page.goto('/payroll');
    expect(resp?.status()).toBeLessThan(400);
  });

  test('/admin/payroll loads successfully', async ({ page }) => {
    const resp = await page.goto('/admin/payroll');
    expect(resp?.status()).toBeLessThan(400);
  });

  test('/admin/reimbursements loads successfully', async ({ page }) => {
    const resp = await page.goto('/admin/reimbursements');
    expect(resp?.status()).toBeLessThan(400);
  });

  test('/approvals loads successfully', async ({ page }) => {
    const resp = await page.goto('/approvals');
    expect(resp?.status()).toBeLessThan(400);
  });
});
