import { test, expect } from '@playwright/test';

test.describe('Approval Pages', () => {
  test('approval index page loads', async ({ page }) => {
    await page.goto('/approvals');
    const text = await page.textContent('body');
    expect(text).toContain('Persetujuan');
  });
});
