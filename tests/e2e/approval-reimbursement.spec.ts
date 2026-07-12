import { test, expect } from '@playwright/test';

test.describe('Approval Pages', () => {
  test('approval index page loads', async ({ page }) => {
    const resp = await page.goto('/approvals');
    expect(resp?.status()).toBeLessThan(400);
    // Tunggu Alpine/Livewire mount
    await page.waitForTimeout(3000);
    const text = await page.textContent('body');
    expect(text).toContain('Persetujuan');
  });
});

test.describe('Reimbursement Pages', () => {
  test('reimbursement index page loads', async ({ page }) => {
    const resp = await page.goto('/admin/reimbursements');
    expect(resp?.status()).toBeLessThan(400);
    const text = await page.textContent('body');
    expect(text.includes('403') || text.includes('500')).toBe(false);
  });
});
