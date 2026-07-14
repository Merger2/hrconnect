import { test, expect } from '@playwright/test';

// Auth handled via storageState (employee) — see playwright.config.js
test.describe('Knowledge Base Chat', () => {
  test('KB chat page loads with welcome screen', async ({ page }) => {
    await page.goto('/knowledge-base');
    await expect(page.locator('h1')).toContainText(/Basis Pengetahuan|Knowledge Base/);
    await expect(page.locator('text=Ada yang bisa dibantu?')).toBeVisible({ timeout: 5000 });
  });

  test('KB chat shows FAQ suggestion buttons', async ({ page }) => {
    await page.goto('/knowledge-base');
    await expect(page.locator('button:has-text("cuti")')).toBeVisible({ timeout: 5000 });
    await expect(page.locator('button:has-text("BPJS")')).toBeVisible();
    await expect(page.locator('button:has-text("lembur")')).toBeVisible();
  });

  test('KB chat textarea accepts input', async ({ page }) => {
    await page.goto('/knowledge-base');
    const textarea = page.locator('textarea[placeholder*="kebijakan"]');
    await expect(textarea).toBeVisible({ timeout: 5000 });
    await textarea.fill('Bagaimana kebijakan cuti tahunan?');
    await expect(textarea).toHaveValue('Bagaimana kebijakan cuti tahunan?');
    await expect(page.locator('button:has(.material-symbols-outlined:has-text("arrow_upward"))')).toBeAttached({ timeout: 5000 });
  });

  test('KB manage page route responds', async ({ page }) => {
    const response = await page.goto('/knowledge-base/manage');
    expect([200, 403]).toContain(response?.status() ?? 0);
  });

  test('KB chat page loads without server error', async ({ page }) => {
    const resp = await page.goto('/knowledge-base');
    expect(resp?.status()).toBeLessThan(500);
    await expect(page.locator('body')).not.toContainText('500');
  });

  test('KB manage page loads without error when authorized', async ({ page }) => {
    const response = await page.goto('/knowledge-base/manage');
    expect(response?.status()).toBeLessThan(500);
  });

  test('KB chat page has a working header', async ({ page }) => {
    await page.goto('/knowledge-base');
    await expect(page.locator('h1')).toBeVisible({ timeout: 5000 });
  });
});
