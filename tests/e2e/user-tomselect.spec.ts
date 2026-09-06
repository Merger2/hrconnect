import { expect, test, type Page } from '@playwright/test';

/**
 * Regression test (2026-08-11) untuk komponen x-user.tom-select-user — sisi
 * user, komplementer admin-tomselect.spec.ts (x-forms.tom-select di admin).
 * Bug class yang di-guard (sudah diperbaiki di 01f4f83..0c9cf8f):
 *   - :options diterima tapi tidak pernah dirender → dropdown kosong
 *   - perubahan dropdown tidak tersimpan ke Livewire (0 request)
 *   - ReferenceError "value is not defined" (race Alpine-vs-module)
 *
 * Target: filter bulan di /attendance-history (wire:model.live="selectedMonth").
 * Alur: buka halaman → buka dropdown (assert 12 opsi ter-render) → pilih bulan
 * berbeda → nilai widget tersync → intercept request Livewire (livewire/update)
 * yang membawa selectedMonth → 0 JS error. Idempoten: filter tidak mengubah DB.
 *
 * Auth: storageState employee.json.
 */

const HISTORY_URL = 'http://localhost:8000/attendance-history';

async function readTomSelectValue(page: Page, selectId: string): Promise<string | null> {
  return page.evaluate((id) => {
    const el = document.getElementById(id) as HTMLSelectElement | null;
    if (!el) return null;
    return el.tomselect ? el.tomselect.getValue() : el.value;
  }, selectId);
}

test('tom-select-user: pilih bulan di attendance-history → 12 opsi ter-render + sync ke Livewire', async ({ page }) => {
  const pageErrors: string[] = [];
  const livewireBodies: string[] = [];

  page.on('pageerror', (e) => pageErrors.push(e.message));
  page.on('console', (m) => {
    if (m.type() === 'error') pageErrors.push(m.text());
  });
  // Tangkap semua request Livewire update (bukti perubahan sampai ke server)
  page.on('request', (req) => {
    if (req.url().includes('/livewire/update')) {
      livewireBodies.push(req.postData() || '');
    }
  });

  // 1. Buka halaman — tidak boleh 500
  const resp = await page.goto(HISTORY_URL, { waitUntil: 'domcontentloaded', timeout: 20000 });
  expect(resp!.status()).toBeLessThan(500);
  await page.waitForSelector('#selectedMonth', { timeout: 10000 });
  await expect(page.locator('body')).not.toContainText(/exception|stack trace|server error|whoops/i);

  // 2. Tunggu TomSelect selesai init
  await expect
    .poll(() => page.evaluate(() => !!document.getElementById('selectedMonth')?.tomselect))
    .toBe(true);

  // Nilai awal datang via Alpine entangle SETELAH Livewire hydrate — SSR <select>
  // sengaja tanpa option selected (wire:ignore). Baca langsung = race (nilai ''),
  // jadi poll sampai nilai sync dari Livewire sebelum lanjut.
  await expect
    .poll(() => readTomSelectValue(page, 'selectedMonth'), { timeout: 5000 })
    .toMatch(/^\d{2}$/);
  const initialMonth = await readTomSelectValue(page, 'selectedMonth');

  // 3. Buka dropdown → 12 opsi bulan harus ter-render (bug lama: 0 opsi).
  //    Dropdown dirender ke <body> (dropdownParent fix 2026-09-05) → opsi
  //    di-locate page-level, difilter yang visible (hanya satu yg terbuka).
  const monthWrapper = page.locator('[data-ui-tomselect-root]:has(#selectedMonth)');
  await monthWrapper.locator('.ts-control').click();
  const options = page.locator('.ts-dropdown .option').filter({ visible: true });
  await expect(options).toHaveCount(12, { timeout: 5000 });
  // Opsi 01..12 harus ada di dropdown (bukan cuma placeholder)
  await expect(page.locator('.ts-dropdown .option[data-value="01"]').filter({ visible: true })).toBeVisible();

  // 4. Pilih bulan yang BERBEDA dari nilai sekarang
  const target = String((Number(initialMonth) % 12) + 1).padStart(2, '0');
  await page.locator(`.ts-dropdown .option[data-value="${target}"]`).filter({ visible: true }).click();

  // 5. Nilai widget harus tersync (handler change hidup)
  await expect.poll(() => readTomSelectValue(page, 'selectedMonth'), { timeout: 5000 }).toBe(target);

  // 6. Perubahan harus sampai ke server: request Livewire update membawa
  //    syncInput selectedMonth = target (bug lama: 0 request sama sekali)
  await expect
    .poll(
      () => livewireBodies.some((body) => body.includes('selectedMonth') && body.includes(`"${target}"`)),
      { timeout: 5000 }
    )
    .toBe(true);

  // 7. Tidak boleh ada JS error (regresi ReferenceError "value is not defined")
  const realErrors = pageErrors.filter(
    (e) => !/favicon|Failed to load resource|Failed to send logs/i.test(e)
  );
  expect(realErrors).toEqual([]);
});

const FILTER_DROPDOWNS = [
  { path: '/attendance-corrections', id: 'correction-status', minOptions: 4 },
  { path: '/hr-tasks', id: 'hr-task-status', minOptions: 2 },
  { path: '/my-tasks', id: 'operational-task-status', minOptions: 2 },
] as const;

for (const { path, id, minOptions } of FILTER_DROPDOWNS) {
  test(`tom-select-user: filter dropdown tetap visible di ${path}`, async ({ page }) => {
    const pageErrors: string[] = [];

    page.on('pageerror', (e) => pageErrors.push(e.message));
    page.on('console', (m) => {
      if (m.type() === 'error') pageErrors.push(m.text());
    });

    const resp = await page.goto(path, { waitUntil: 'domcontentloaded', timeout: 20000 });
    expect(resp!.status()).toBeLessThan(500);
    await page.waitForSelector(`#${id}`, { timeout: 10000 });
    await expect(page.locator('body')).not.toContainText(/exception|stack trace|server error|whoops/i);

    await expect
      .poll(() => page.evaluate((selectId) => !!document.getElementById(selectId)?.tomselect, id))
      .toBe(true);

    const wrapper = page.locator(`[data-ui-tomselect-root]:has(#${id})`);
    await wrapper.locator('.ts-control').click();

    const options = page.locator('.ts-dropdown .option').filter({ visible: true });
    await expect.poll(() => options.count(), { timeout: 5000 }).toBeGreaterThanOrEqual(minOptions);

    const zIndex = await page
      .locator('.ts-dropdown')
      .filter({ visible: true })
      .first()
      .evaluate((el) => window.getComputedStyle(el).zIndex);
    expect(Number(zIndex)).toBeGreaterThanOrEqual(99999);

    await page.waitForTimeout(1200);
    await expect(options.first()).toBeVisible();

    const realErrors = pageErrors.filter(
      (e) => !/favicon|Failed to load resource|Failed to send logs/i.test(e)
    );
    expect(realErrors).toEqual([]);
  });
}

test('home notification dropdown floats above the employee home cards', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });

  const resp = await page.goto('/home', { waitUntil: 'domcontentloaded', timeout: 20000 });
  expect(resp!.status()).toBeLessThan(500);

  const trigger = page.locator('.user-home-hero__tools .notifications-trigger');
  await expect(trigger).toBeVisible();
  await trigger.click();

  const panel = page.locator('#notifications-panel').filter({ visible: true });
  await expect(panel).toBeVisible();

  const layerCheck = await panel.evaluate((el) => {
    const rect = el.getBoundingClientRect();
    const x = rect.left + Math.min(rect.width / 2, 24);
    const y = rect.top + Math.min(rect.height / 2, 48);
    const topElement = document.elementFromPoint(x, y);

    return {
      panelZ: Number(window.getComputedStyle(el).zIndex),
      topElementIsPanel: topElement ? el.contains(topElement) || topElement === el : false,
    };
  });

  expect(layerCheck.panelZ).toBeGreaterThanOrEqual(50);
  expect(layerCheck.topElementIsPanel).toBe(true);
});
