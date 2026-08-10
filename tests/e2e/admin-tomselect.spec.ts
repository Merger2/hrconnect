import { expect, test, type Page } from '@playwright/test';

/**
 * Regression test (2026-08-11) untuk bug class tom-select yang sudah diperbaiki
 * di commit 01f4f83..0c9cf8f:
 *   - dropdown selalu menampilkan opsi pertama walau DB beda (sync entangle mati)
 *   - perubahan dropdown tidak pernah tersimpan ke Livewire (0 request)
 *   - ReferenceError "value is not defined" (race Alpine-vs-module)
 *
 * Alur: edit employee (Hana Salsabila, id 44 dari main seeder) → pilih opsi lain
 * di dropdown Employment Type (x-forms.select yang me-render tom-select) →
 * Update → buka ulang halaman edit → nilai harus PERSIST → revert ke nilai awal
 * → verifikasi revert JUGA persist (test idempoten-terverifikasi).
 *
 * BUTUH data seeder: Employee id 44 (employment_type contract). Auth:
 * storageState admin.json.
 */

const EMPLOYEE_NAME = 'Hana Salsabila';
const EMPLOYEE_ID = 44;
const EDIT_URL = `http://localhost:8000/admin/employees/${EMPLOYEE_ID}/edit`;
const EMPLOYMENT_OPTIONS = ['permanent', 'contract', 'intern'] as const;

/** Baca nilai tom-select via instance TomSelect (el.tomselect) atau native select. */
async function readTomSelectValue(page: Page, selectId: string): Promise<string | null> {
  return page.evaluate((id) => {
    const el = document.getElementById(id) as HTMLSelectElement | null;
    if (!el) return null;
    return el.tomselect ? el.tomselect.getValue() : el.value;
  }, selectId);
}

/** Pilih opsi via klik fisik dropdown (simulasi user), bukan selectOption native. */
async function pickTomSelectOption(page: Page, selectId: string, value: string): Promise<void> {
  const wrapper = page.locator(`[data-ui-tomselect-root]:has(#${selectId})`);
  await expect(wrapper).toBeVisible();

  // Buka dropdown; dropdown di-render di dalam wrapper (scoped, hindari tabrakan
  // strict-mode dengan tom-select lain di halaman)
  await wrapper.locator('.ts-control').click();
  const option = wrapper.locator(`.ts-dropdown .option[data-value="${value}"]`);
  await expect(option).toBeVisible({ timeout: 5000 });
  await option.click();

  // Nilai harus terset di widget (kalau handler sync mati, ini akan timeout)
  await expect.poll(() => readTomSelectValue(page, selectId), { timeout: 5000 }).toBe(value);
}

/** Buka halaman edit via URL: domcontentloaded (networkidle bisa timeout karena
 *  polling Livewire). Retry: setelah submit Livewire masih ada request pending
 *  yang bisa meng-abort navigasi berikutnya (net::ERR_ABORTED). */
async function gotoEditPage(page: Page): Promise<void> {
  await expect
    .poll(
      async () => {
        const resp = await page.goto(EDIT_URL, { waitUntil: 'domcontentloaded', timeout: 20000 });
        return resp?.status() ?? 0;
      },
      { timeout: 20000, intervals: [1000, 2000] }
    )
    .toBeLessThan(500);
  await page.waitForSelector('#edit_employment_type', { timeout: 10000 });
  await expect(page.locator('body')).not.toContainText(/exception|stack trace|server error|whoops/i);
}

/** Dari halaman index: search nama → klik link edit baris tsb → tunggu form siap. */
async function openEditFromIndex(page: Page): Promise<void> {
  await page.fill('#employee-search', EMPLOYEE_NAME);
  const row = page.locator('tbody tr', { hasText: EMPLOYEE_NAME });
  await expect(row).toBeVisible({ timeout: 10000 });
  // href edit (label bisa terjemahan: "Ubah karyawan" — jangan andalkan teks)
  await row.locator('a[href*="/edit"]').click();
  await page.waitForSelector('#edit_employment_type', { timeout: 10000 });
  await expect
    .poll(() => page.evaluate(() => !!document.getElementById('edit_employment_type')?.tomselect))
    .toBe(true);
}

test('tom-select: pilih employment type di edit employee → persist di DB (re-open + revert)', async ({ page }) => {
  const pageErrors: string[] = [];
  page.on('pageerror', (e) => pageErrors.push(e.message));
  page.on('console', (m) => {
    if (m.type() === 'error') pageErrors.push(m.text());
  });

  // 1. Buka halaman edit — pastikan tidak 500 DAN employee yang benar (guard
  //    hardcode id: kalau seeder berubah, test gagal jelas bukan diam-diam)
  await gotoEditPage(page);
  await expect(page.locator('#edit_name')).toHaveValue(EMPLOYEE_NAME);

  const initial = await readTomSelectValue(page, 'edit_employment_type');
  expect(EMPLOYMENT_OPTIONS).toContain(initial);

  // 2. Pilih opsi yang BERBEDA dari nilai sekarang
  const target = EMPLOYMENT_OPTIONS.find((o) => o !== initial)!;
  await pickTomSelectOption(page, 'edit_employment_type', target);

  // 3. Submit form (wire:submit="update" → redirect ke index)
  await page.getByRole('button', { name: 'Update Employee' }).click();
  await page.waitForURL('**/admin/employees**', { timeout: 15000 });
  await expect(page.locator('body')).not.toContainText(/exception|stack trace|server error|whoops/i);

  // 4. Buka ulang halaman edit → nilai baru harus PERSIST (bug lama: tetap opsi pertama)
  await openEditFromIndex(page);
  await expect.poll(() => readTomSelectValue(page, 'edit_employment_type'), { timeout: 5000 }).toBe(target);

  // 5. Revert ke nilai awal DAN verifikasi revert benar-benar persist — tanpa
  //    assertion ini, revert yang gagal diam-diam tetap membuat test pass
  //    (terbukti saat development: test 6/6 hijau tapi DB tertinggal "intern").
  await pickTomSelectOption(page, 'edit_employment_type', initial!);
  await page.getByRole('button', { name: 'Update Employee' }).click();
  await page.waitForURL('**/admin/employees**', { timeout: 15000 });

  await openEditFromIndex(page);
  await expect.poll(() => readTomSelectValue(page, 'edit_employment_type'), { timeout: 5000 }).toBe(initial);

  // 6. Tidak boleh ada JS error (regresi ReferenceError "value is not defined").
  //    "Failed to send logs" = noise infra dari flushLogs halaman index
  //    (analitik/log sender, bukan regresi tom-select).
  const realErrors = pageErrors.filter(
    (e) => !/favicon|Failed to load resource|Failed to send logs/i.test(e)
  );
  expect(realErrors).toEqual([]);
});
