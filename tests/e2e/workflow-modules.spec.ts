import { expect, test, type Page } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

/**
 * Workflow kasus nyata modul lain (komplementer workflow-approvals.spec.ts utk
 * cuti). Tiap modul: employee submit via UI (note unik → idempoten) → manager
 * approve L1 → L2 (admin HR / finance) → status final terlihat di UI admin.
 * Bukan mock: browser asli + DB dev (E2eTestSeeder) + Livewire nyata.
 *
 * Peran state per describe (storageState), serial di level file.
 */

const BASE = 'http://localhost:8000';
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const authDir = path.join(__dirname, '.auth');

const employeeState = path.join(authDir, 'employee.json');
const managerState = path.join(authDir, 'manager.json');
const hrState = path.join(authDir, 'hr.json');
const financeState = path.join(authDir, 'finance.json');
const adminState = hrState; // Test HR = admin yang memegang manageCashAdvances

test.describe.configure({ mode: 'serial' });

// ── helper ────────────────────────────────────────────────────────────────
async function clickManagerApproveCard(page: Page, text: string, tabLabel: RegExp): Promise<void> {
    await page.goto(`${BASE}/approvals`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    const tab = page.locator('.team-approval-tab', { hasText: tabLabel });
    if (await tab.count()) {
        await tab.click();
    }
    // Cari card di semua halaman paginasi daftar approval
    let card = page.locator('.team-approval-card', { hasText: text });
    for (let pageNo = 0; pageNo < 10 && (await card.count()) === 0; pageNo += 1) {
        const next = page.locator('a[rel="next"], button[rel="next"]').first();
        if (!(await next.isVisible().catch(() => false))) break;
        await next.click();
        await page.waitForTimeout(1500);
        card = page.locator('.team-approval-card', { hasText: text });
    }
    await expect(card.first()).toBeVisible({ timeout: 30000 });
    for (let attempt = 0; attempt < 5; attempt += 1) {
        const live = page.locator('.team-approval-card', { hasText: text }).first();
        if ((await live.count()) === 0) break;
        const btn = live.locator('.team-approval-action--approve').first();
        if (await btn.isVisible().catch(() => false)) {
            await btn.click();
        }
        await page.waitForTimeout(2000);
    }
    await expect(page.locator('.team-approval-card', { hasText: text })).toHaveCount(0, { timeout: 30000 });
}

async function clickAdminApprove(page: Page, urlPath: string, text: string, maxClicks = 5): Promise<void> {
    await page.goto(`${BASE}${urlPath}`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    // wire:confirm memakai window.confirm — Playwright default dismiss; accept biar aksi jalan
    page.on('dialog', (d) => d.accept());
    // Cari baris di semua halaman paginasi
    let row = page.locator('tr').filter({ hasText: text });
    for (let pageNo = 0; pageNo < 10 && (await row.count()) === 0; pageNo += 1) {
        const next = page.locator('a[rel="next"], button[rel="next"]').first();
        if (!(await next.isVisible().catch(() => false))) break;
        await next.click();
        await page.waitForTimeout(1500);
        row = page.locator('tr').filter({ hasText: text });
    }
    await expect(row.first()).toBeVisible({ timeout: 30000 });
    const approveBtn = row.first().locator('button[wire\\:click^="approve"]');
    for (let attempt = 0; attempt < maxClicks; attempt += 1) {
        if ((await approveBtn.count()) === 0) break;
        if (await approveBtn.first().isVisible().catch(() => false)) {
            await approveBtn.first().click();
        }
        await page.waitForTimeout(2000);
    }
    await expect(approveBtn).toHaveCount(0, { timeout: 30000 });
}

async function pickTomSelect(page: Page, selectId: string, optionText: string): Promise<void> {
    const wrapper = page.locator(`[data-ui-tomselect-root]:has(#${selectId})`);
    await expect
        .poll(() => page.evaluate((id) => !!document.getElementById(id)?.tomselect, selectId))
        .toBe(true);
    // Bandingkan nilai AKTUAL vs nilai opsi target (guard lama cek [data-value]
    // — kalau widget sudah punya pilihan awal (filter admin default 'pending'),
    // pick di-skip padahal perlu ganti → flaky).
    const desired = await page.evaluate(
        ({ id, text }) => {
            const sel = document.getElementById(id) as HTMLSelectElement | null;
            const opt = [...(sel?.options ?? [])].find((o) => o.textContent?.trim().includes(text));
            return opt ? opt.value : null;
        },
        { id: selectId, text: optionText },
    );
    const current = await page.evaluate(
        (id) => (document.getElementById(id) as any)?.tomselect?.getValue() ?? '',
        selectId,
    );
    if (desired && current !== desired) {
        // Retry sampai nilai benar-benar berubah: klik pertama bisa terjadi
        // saat Livewire belum hydrate (handler entangle belum terpasang) →
        // pilihan visual berubah tapi model tidak sync → filter diam.
        const readValue = () =>
            page.evaluate((id) => (document.getElementById(id) as any)?.tomselect?.getValue() ?? '', selectId);
        for (let attempt = 0; attempt < 3 && (await readValue()) !== desired; attempt += 1) {
            await wrapper.locator('.ts-control').click({ timeout: 5000 }).catch(() => null);
            await page.waitForTimeout(800);
            // Opsi di-locate page-level: dropdown user dirender ke <body>
            // (dropdownParent fix 2026-09-05), admin tetap in-wrapper — hanya
            // satu dropdown yang terbuka, jadi filter visible cukup. Klik boleh
            // gagal (dropdown belum selesai render pasca-hydrate) → retry loop.
            const option = page
                .locator('.ts-dropdown .option')
                .filter({ visible: true, hasText: optionText })
                .first();
            try {
                await option.click({ timeout: 5000 });
            } catch {
                /* dropdown belum siap — coba buka lagi di iterasi berikutnya */
            }
            await page.waitForTimeout(800);
        }
    }
}

// Input tanggal/waktu memakai Flatpickr (readonly text). Set via API instance
// (el._flatpickr.setDate) supaya state & Livewire sync — isi manual tidak bisa.
async function setPicker(page: Page, selector: string, value: string): Promise<void> {
    const ok = await page.evaluate(
        ({ sel, val }) => {
            const el = document.getElementById(sel) as HTMLInputElement | null;
            if (!el) return false;
            if (el._flatpickr) {
                el._flatpickr.setDate(val, true);
            } else {
                el.value = val;
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
            return true;
        },
        { sel: selector, val: value },
    );
    expect(ok).toBe(true);
}

// Untuk input TANGGAL: set value ISO langsung + dispatch — Livewire membaca
// value input (bukan state internal flatpickr). setDate() flatpickr memformat
// ulang ke 'd M Y' yang justru merusak nilai model.
async function setDateRaw(page: Page, selector: string, iso: string): Promise<void> {
    await page.evaluate(
        ({ sel, val }) => {
            const el = document.getElementById(sel) as HTMLInputElement | null;
            if (!el) return;
            el.value = val;
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        },
        { sel: selector, val: iso },
    );
}

async function setFutureWeekday(page: Page, selector: string, minDays: number): Promise<string> {
    const iso = await page.evaluate((min) => {
        const fmt = (d: Date): string =>
            `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        const today = new Date();
        for (let i = min; i <= min + 14; i += 1) {
            const d = new Date(today.getFullYear(), today.getMonth(), today.getDate() + i);
            const dow = d.getDay();
            if (dow !== 0 && dow !== 6) {
                return fmt(d);
            }
        }
        return '';
    }, minDays);
    expect(iso).not.toBe('');
    await setDateRaw(page, selector, iso);
    return iso;
}

// ============================================================================
// 1) LEMBUR — submit → manager approve (FINAL; UI lembur 1 level)
// ============================================================================
const OT_REASON = `E2E lembur ${Date.now()}`;
let otLabel = '';

const monthsEn = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
function isoToDMY(iso: string): string {
    const [y, m, d] = iso.split('-').map(Number);
    return `${d} ${monthsEn[m - 1]} ${y}`;
}

// Daftar (lembur/reimburse) di-paginasi 10/halaman — item uji bisa jatuh di
// halaman >1. Helper ini memindai semua halaman lewat teks body.
async function findCardAcrossPages(page: Page, text: string): Promise<boolean> {
    for (let i = 0; i < 10; i += 1) {
        const bodyText = await page.locator('body').innerText().catch(() => '');
        if (bodyText.includes(text)) {
            return true;
        }
        const next = page.locator('a[rel="next"], button[rel="next"], a:has-text("Next"), a[aria-label*="Next" i]').first();
        if (!(await next.isVisible().catch(() => false))) {
            return false;
        }
        await next.click();
        await page.waitForTimeout(1500);
    }
    return false;
}

async function submitOvertimeWithRetry(page: Page): Promise<string> {
    await page.locator('button[wire\\:click="create"]').first().click();
    await page.waitForSelector('#date', { timeout: 10000 });
    const modalForm = page.locator('form').filter({ has: page.locator('#reason') }).first();

    // Coba beberapa kandidat tanggal berjauhan (30/60/90/120/150 hari) karena
    // run E2E berulang membuat lembur pada hari yang sama → validasi overlap.
    for (const offset of [30, 60, 90, 120, 150]) {
        const jitter = Math.floor(Math.random() * 20);
        const otIso = await setFutureWeekday(page, 'date', offset + jitter);
        await setPicker(page, 'start_time', '18:00');
        await setPicker(page, 'end_time', '21:00');
        await page.locator('#reason').fill(`${OT_REASON} ${otIso}`);
        await page.locator('#reason').dispatchEvent('change'); // wire:model non-live sync on change

        await modalForm.locator('button').filter({ hasText: /submit|kirim/i }).last().click();

        const closed = await modalForm
            .waitFor({ state: 'hidden', timeout: 6000 })
            .then(() => true)
            .catch(() => false);
        if (closed) {
            return otIso;
        }
        // Modal masih terbuka (overlap tanggal) → ganti tanggal & coba lagi di modal sama
    }
    throw new Error('lembur submit gagal pada semua kandidat tanggal');
}

test.describe('workflow lembur: employee submit', () => {
    test('submit lembur via /overtime (modal)', async ({ page }) => {
        await page.goto(`${BASE}/overtime`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        otLabel = isoToDMY(await submitOvertimeWithRetry(page)); // dibagikan ke test verifikasi
        // Alasan unik per run (Date.now) — cari di semua halaman daftar
        expect(await findCardAcrossPages(page, OT_REASON)).toBe(true);
    });
});

test.describe('workflow lembur: manager L1', () => {
    test.use({ storageState: managerState });
    test('manager approve lembur di /approvals', async ({ page }) => {
        await clickManagerApproveCard(page, OT_REASON, /Lembur/i);
    });
});

test.describe('workflow lembur: verifikasi final', () => {
    test.use({ storageState: employeeState });
    test('pengajuan lembur berstatus Disetujui di daftar employee', async ({ page }) => {
        await page.goto(`${BASE}/overtime`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        const found = await findCardAcrossPages(page, OT_REASON);
        expect(found).toBe(true);
        const card = page.locator('.user-list-card').filter({ hasText: OT_REASON }).first();
        await expect(card).toContainText(/disetujui|approved/i, { timeout: 5000 });
    });
});

// ============================================================================
// 2) KASBON — submit → manager L1 → finance L2 (/admin/manage-kasbon)
// ============================================================================
const KASBON_PURPOSE = `E2E kasbon ${Date.now()}`;
// Amount unik per run supaya assertion di daftar (yg tak menampilkan purpose) tak ambigu
const KASBON_AMOUNT = 1_000_000 + (Date.now() % 8_000_000);
const KASBON_AMOUNT_FMT = String(KASBON_AMOUNT).replace(/\B(?=(\d{3})+(?!\d))/g, '.');

test.describe('workflow kasbon: employee submit', () => {
    test('submit kasbon via /my-kasbon (modal)', async ({ page }) => {
        await page.goto(`${BASE}/my-kasbon`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        await page.locator('button[wire\\:click="openCreateModal"]').click();
        await page.waitForSelector('#kasbon-amount', { timeout: 10000 });
        await page.locator('#kasbon-amount').fill(String(KASBON_AMOUNT));
        await page.locator('#kasbon-purpose').fill(KASBON_PURPOSE);
        await page.locator('#kasbon-purpose').dispatchEvent('change');

        // Scope ke form kasbon (hindari tombol logout type=submit di layout)
        const kasbonForm = page.locator('#kasbon-amount').locator('xpath=ancestor::form');
        const submitBtn = kasbonForm.locator('button[type="submit"]');
        await Promise.all([
            page.waitForResponse((r) => r.url().includes('/livewire/update') && r.ok(), { timeout: 30000 }),
            submitBtn.click(),
        ]);
        // Modal tertutup = sukses; daftar menampilkan nominal unik + status pending
        await expect(kasbonForm).toBeHidden({ timeout: 15000 });
        await expect(page.locator('body')).toContainText(KASBON_AMOUNT_FMT, { timeout: 15000 });
    });
});

test.describe('workflow kasbon: manager L1', () => {
    test.use({ storageState: managerState });
    test('manager approve kasbon di /approvals', async ({ page }) => {
        await clickManagerApproveCard(page, KASBON_PURPOSE, /Kasbon/i);
    });
});

// L2 kasbon (final) = pemegang `manageCashAdvances` (Test HR/Super Admin) di
// /admin/manage-kasbon — role Finance di seed adalah Staff (bukan Finance Head).
test.describe('workflow kasbon: admin L2', () => {
    test.use({ storageState: adminState });
    test('admin approve kasbon di /admin/manage-kasbon → approved', async ({ page }) => {
        // Halaman admin menampilkan nominal (bukan purpose) — filter baris via nominal unik
        await clickAdminApprove(page, '/admin/manage-kasbon', KASBON_AMOUNT_FMT, 3);
    });
});

// ============================================================================
// 3) REIMBURSEMENT — submit → manager L1 → finance L2 (/admin/reimbursements)
// ============================================================================
const REIMB_DESC = `E2E reimbursement ${Date.now()}`;

test.describe('workflow reimbursement: employee submit', () => {
    test('submit reimbursement via /reimbursement', async ({ page }) => {
        await page.goto(`${BASE}/reimbursement`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        await page.locator('button[wire\\:click="create"]').first().click();
        await page.waitForSelector('#type', { timeout: 10000 });
        await pickTomSelect(page, 'type', 'Transportasi');
        // Textarea tidak punya id → scope via wire:model
        await page.locator('textarea[wire\\:model="description"]').fill(REIMB_DESC);

        // Tanggal transaksi: 2 hari lalu
        const twoDaysAgo = await page.evaluate(() => {
            const d = new Date(Date.now() - 2 * 86400000);
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        });
        await setDateRaw(page, 'reimbursement-date', twoDaysAgo);

        await page.locator('input[wire\\:model="amount"]').fill('200000');

        // Form create = container reimbursement-date; hindari tombol logout type=submit di layout
        const form = page.locator('form').filter({ has: page.locator('#reimbursement-date') }).first();
        await Promise.all([
            page.waitForResponse((r) => r.url().includes('/livewire/update') && r.ok(), { timeout: 30000 }),
            form.locator('button[type="submit"]').click(),
        ]);
        // Sukses = form create tertutup kembali ke daftar; alur penuh diverifikasi di
        // langkah manager (L1) & superadmin (L2) berikutnya via deskripsi unik
        await expect(form).toBeHidden({ timeout: 20000 });
        await expect(page.locator('button[wire\\:click="create"]').first()).toBeVisible({ timeout: 15000 });
    });
});

test.describe('workflow reimbursement: manager L1', () => {
    test.use({ storageState: managerState });
    test('manager approve reimbursement di /approvals', async ({ page }) => {
        await clickManagerApproveCard(page, REIMB_DESC, /Claim|Klaim/i);
    });
});

test.describe('workflow reimbursement: finance L2', () => {
    test.use({ storageState: financeState });
    test('finance approve reimbursement di /admin/reimbursements → approved', async ({ page }) => {
        await clickAdminApprove(page, '/admin/reimbursements', REIMB_DESC, 3);
    });
});
