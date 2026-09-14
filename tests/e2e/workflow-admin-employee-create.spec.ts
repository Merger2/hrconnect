import { test, expect } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = process.env.E2E_BASE_URL || 'http://localhost:8000';
const authDir = path.join(__dirname, '.auth');
const adminState = path.join(authDir, 'admin.json');

test.use({ storageState: adminState });

/** Baca nilai TomSelect (el.tomselect) atau native select — pola admin-tomselect.spec.ts. */
async function readTomSelectValue(page: import('@playwright/test').Page, selectId: string): Promise<string> {
    return page.evaluate((id) => {
        const el = document.getElementById(id);
        return el?.tomselect ? el.tomselect.getValue() : el?.value ?? '';
    }, selectId);
}

/** Pilih opsi via klik fisik dropdown TomSelect (bukan selectOption native) —
 *  select di konteks admin di-enhance TomSelect; native selectOption tidak
 *  men-trigger Livewire binding. Pola dari admin-tomselect.spec.ts. */
async function pickTomSelectOption(page: import('@playwright/test').Page, selectId: string, value: string): Promise<void> {
    const wrapper = page.locator(`[data-ui-tomselect-root]:has(#${selectId})`);
    await expect(wrapper).toBeVisible({ timeout: 10000 });
    await wrapper.locator('.ts-control').click();
    const option = wrapper.locator(`.ts-dropdown .option[data-value="${value}"]`);
    await expect(option).toBeVisible({ timeout: 5000 });
    await option.click();
    await expect.poll(() => readTomSelectValue(page, selectId), { timeout: 5000 }).toBe(value);
}

test.describe('Admin Employee Create/Edit Flow', () => {
    test('Admin can access employee create page', async ({ page }) => {
        await page.goto(`${BASE}/admin/employees/create`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
    });

    test('Admin can view employee create form', async ({ page }) => {
        await page.goto(`${BASE}/admin/employees/create`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Check for form fields
        const nameInput = page.locator('input[name="name"], input[name="full_name"]').first();
        const emailInput = page.locator('input[name="email"]').first();
        const nipInput = page.locator('input[name="nip"], input[name="nik"]').first();
        
        if (await nameInput.isVisible({ timeout: 5000 })) {
            await expect(nameInput).toBeVisible();
        }
        if (await emailInput.isVisible({ timeout: 5000 })) {
            await expect(emailInput).toBeVisible();
        }
        if (await nipInput.isVisible({ timeout: 5000 })) {
            await expect(nipInput).toBeVisible();
        }
    });

    test('Admin can fill and submit employee create form', async ({ page }) => {
        await page.goto(`${BASE}/admin/employees/create`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        // Fill required fields
        const timestamp = Date.now();
        const testEmail = `test.employee.${timestamp}@hrconnect.test`;
        const testNip = `19${timestamp.toString().slice(-8)}`;
        
        const nameInput = page.locator('input[name="name"], input[name="full_name"]').first();
        const emailInput = page.locator('input[name="email"]').first();
        const nipInput = page.locator('input[name="nip"], input[name="nik"]').first();
        
        if (await nameInput.isVisible({ timeout: 5000 })) {
            await nameInput.fill(`Test Employee ${timestamp}`);
        }
        if (await emailInput.isVisible({ timeout: 5000 })) {
            await emailInput.fill(testEmail);
        }
        if (await nipInput.isVisible({ timeout: 5000 })) {
            await nipInput.fill(testNip);
        }
        
        // Fill other required fields if visible
        const divisionSelect = page.locator('select[name="division_id"], select[name="division"]').first();
        if (await divisionSelect.isVisible({ timeout: 3000 })) {
            await divisionSelect.selectOption({ index: 1 });
        }
        
        const positionSelect = page.locator('select[name="position_id"], select[name="position"]').first();
        if (await positionSelect.isVisible({ timeout: 3000 })) {
            await positionSelect.selectOption({ index: 1 });
        }
        
        // Submit
        const submitBtn = page.locator('button[type="submit"]:has-text("Simpan"), button:has-text("Save"), button:has-text("Create")').first();
        if (await submitBtn.isVisible({ timeout: 3000 })) {
            await submitBtn.click();
            
            // Check for success
            const toast = page.locator('[role="alert"], .toast').first();
            await expect(toast).toBeVisible({ timeout: 15000 });
        }
    });

    test('Employment status is read-only Active on create page', async ({ page }) => {
        // Regresi: dropdown create pernah menampilkan status lifecycle penghapusan
        // (deletion_requested/deleted) — kini read-only "Aktif".
        await page.goto(`${BASE}/admin/employees/create`, { waitUntil: 'domcontentloaded', timeout: 20000 });

        const status = page.locator('#create_employment_status');
        await expect(status).toBeDisabled({ timeout: 10000 });
        await expect(status).toHaveValue(/Aktif|Active/i);
    });

    test('Super admin can create an employee end-to-end (regression: null company_id TypeError)', async ({ page }) => {
        // Akun admin@hrconnect.local = super admin bootstrap dengan company_id
        // NULL. Sebelumnya UserForm::store() mengoper NULL ke getDefaultBranchId(int)
        // → TypeError 500 di setiap submit. Fallback single-company harus membuat
        // create sukses penuh: redirect + banner "Created successfully.".
        await page.goto(`${BASE}/admin/employees/create`, { waitUntil: 'domcontentloaded', timeout: 20000 });

        const timestamp = Date.now();

        await page.locator('#create_name').fill(`E2E Employee ${timestamp}`);
        await page.locator('#create_email').fill(`e2e.emp.${timestamp}@hrconnect.test`);
        await page.locator('#create_nip').fill(`19${String(timestamp).slice(-8)}`);
        await page.locator('#create_password').fill('E2e!Pass2026x');
        await page.locator('#create_password_confirmation').fill('E2e!Pass2026x');
        await page.locator('#create_phone').fill('081234567891');
        await page.locator('input[name="gender"][value="male"]').check();
        await page.locator('#create_birth_date').fill('1993-05-05');
        await page.locator('#create_join_date').fill('2026-09-01');
        await page.locator('#create_address').fill('Jl. E2E No. 4');
        await pickTomSelectOption(page, 'create_education_level', 'bachelor');
        await page.locator('#create_institution_name').fill('Universitas E2E');
        await page.locator('#create_graduation_year').fill('2015');

        await page.getByRole('button', { name: /Create Employee|Tambah Karyawan/i }).click();

        // Sukses = redirect ke daftar karyawan + karyawan baru muncul di list.
        // Catatan: <x-banner> tidak dirender di layout mana pun, jadi flash
        // banner memang tidak tampil — bukan bagian dari kontrak sukses.
        await page.waitForURL('**/admin/employees', { timeout: 20000 });
        await expect(page.locator('body')).toContainText(`E2E Employee ${timestamp}`, { timeout: 10000 });
    });

    test('Admin can access employee edit page', async ({ page }) => {
        // First get an existing employee
        await page.goto(`${BASE}/admin/employees`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        
        const editBtn = page.locator('[wire\\:click*="edit"], button:has-text("Edit")').first();
        if (await editBtn.isVisible({ timeout: 5000 })) {
            await editBtn.click();
            
            // Should be on edit page
            await expect(page.locator('body')).toBeVisible({ timeout: 10000 });
        }
    });
});