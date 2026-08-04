import { test, expect } from '@playwright/test';

test('PWA manifest is linked and valid', async ({ page }) => {
  await page.goto('/login');

  const manifestHref = await page.locator('link[rel="manifest"]').getAttribute('href');
  expect(manifestHref).toBeTruthy();

  const response = await page.request.get(manifestHref!);
  expect(response.ok()).toBeTruthy();
  const manifest = await response.json();
  expect(manifest.name).toBeTruthy();
  expect(manifest.icons.length).toBeGreaterThan(0);

  const swRegistered = await page.evaluate(async () => {
    if (!('serviceWorker' in navigator)) return false;
    const reg = await navigator.serviceWorker.getRegistration();
    return !!reg;
  });
  expect(swRegistered).toBeTruthy();
});
