import { test, expect } from '@playwright/test';

test.describe('Role: HR-Manager — all accessible pages', () => {
  test.use({ storageState: 'tests/e2e/.auth/hr.json' });

  test('/dashboard loads successfully', async ({ page }) => {
    const resp = await page.goto('/dashboard');
    expect(resp?.status()).toBeLessThan(400);
  });

  test('/admin/employees loads successfully', async ({ page }) => {
    const resp = await page.goto('/admin/employees');
    expect(resp?.status()).toBeLessThan(400);
  });

  test('/admin/employees/create loads successfully', async ({ page }) => {
    const resp = await page.goto('/admin/employees/create');
    expect(resp?.status()).toBeLessThan(400);
  });

  test('/attendance/clock-in loads successfully', async ({ page }) => {
    const resp = await page.goto('/attendance/clock-in');
    expect(resp?.status()).toBeLessThan(400);
  });

  test('/leaves loads successfully', async ({ page }) => {
    const resp = await page.goto('/leaves');
    expect(resp?.status()).toBeLessThan(400);
  });

  test('/overtimes loads successfully', async ({ page }) => {
    const resp = await page.goto('/overtimes');
    expect(resp?.status()).toBeLessThan(400);
  });

  test('/reimbursements loads successfully', async ({ page }) => {
    const resp = await page.goto('/reimbursements');
    expect(resp?.status()).toBeLessThan(400);
  });

  test('/loans loads successfully', async ({ page }) => {
    const resp = await page.goto('/loans');
    expect(resp?.status()).toBeLessThan(400);
  });

  test('/assets loads successfully', async ({ page }) => {
    const resp = await page.goto('/assets');
    expect(resp?.status()).toBeLessThan(400);
  });

});
