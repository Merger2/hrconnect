import { test, expect } from '@playwright/test';

test.describe('Pinjaman (Loan) Flow', () => {
  test('loans index page loads', async ({ page }) => {
    await page.goto('/loans');
    await expect(page.locator('text=Pengajuan pinjaman karyawan')).toBeVisible({ timeout: 10000 });
  });

  test('loans page has status filter', async ({ page }) => {
    await page.goto('/loans');
    await expect(page.locator('select[x-model="statusFilter"]')).toBeVisible({ timeout: 10000 });
  });

  test('loans page has create button', async ({ page }) => {
    await page.goto('/loans');
    await expect(page.locator('text=Tambah Pinjaman')).toBeVisible({ timeout: 10000 });
  });
});
