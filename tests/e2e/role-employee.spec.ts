import { test, expect } from '@playwright/test';

test.describe('Role: Employee — all accessible pages', () => {
  test.use({ storageState: 'tests/e2e/.auth/employee.json' });

  test('/dashboard loads successfully', async ({ page }) => {
    const resp = await page.goto('/dashboard');
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

  test('/payroll loads successfully', async ({ page }) => {
    const resp = await page.goto('/payroll');
    expect(resp?.status()).toBeLessThan(400);
  });

  test('/knowledge-base loads successfully', async ({ page }) => {
    const resp = await page.goto('/knowledge-base');
    expect(resp?.status()).toBeLessThan(400);
  });
});
