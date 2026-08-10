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

  // SW diregistrasi dari /login dengan scope '/build/' (register('/build/sw.js')
  // tanpa scope eksplisit → scope dihitung dari path script). Karena scope bukan
  // '/', navigator.serviceWorker.getRegistration() (tanpa argumen, scope halaman
  // saat ini) mengembalikan null — harus pakai getRegistrations() + cek scriptURL.
  // Polling via waitForFunction untuk menghindari race dengan registrasi async
  // (dijalankan di event 'load' + registration.update()).
  const swRegistered = await page
    .waitForFunction(
      async () => {
        if (!('serviceWorker' in navigator)) return false;
        const registrations = await navigator.serviceWorker.getRegistrations();
        return registrations.some(
          (r) => r.active && r.active.scriptURL.endsWith('/build/sw.js'),
        );
      },
      null,
      { timeout: 10000 },
    )
    .then(() => true)
    .catch(() => false);

  expect(swRegistered).toBeTruthy();
});
