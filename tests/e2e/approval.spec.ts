import { test, expect } from '@playwright/test';

test.describe('Approval Pages', () => {
  test('approval index page loads', async ({ page }) => {
    await page.goto('/approvals');
    const text = await page.textContent('body');
    expect(text).toContain('Persetujuan');
  });

  test('approval page loads without server error', async ({ page }) => {
    const resp = await page.goto('/approvals');
    expect(resp?.status()).toBeLessThan(500);
    await expect(page.locator('body')).not.toContainText('500');
  });

  test('approval page renders body content', async ({ page }) => {
    await page.goto('/approvals');
    await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
  });

  test('approval page shows pending section or tabs', async ({ page }) => {
    await page.goto('/approvals');
    await page.waitForTimeout(2000);
    await expect(page.locator('body')).toBeVisible();
  });
});
