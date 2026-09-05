import { expect, test, type Page } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

/**
 * Workflow kasus nyata lintas role — komplementer:
 *   - workflow-approvals.spec.ts  → cuti (employee → manager L1 → HR L2)
 *   - workflow-modules.spec.ts    → lembur, kasbon, reimbursement
 *   - workflow-extra.spec.ts      → WFH, tukar shift, koreksi absensi (2 level)
 *
 * Bukan mock: browser asli + DB dev (E2eTestSeeder/E2eOperationalDataSeeder)
 * + Livewire request sungguhan. Idempoten: note/reason unik per run (Date.now),
 * tidak menabrak seeded requests.
 *
 * Auth: storageState role berbeda per describe; serial di level file.
 *
 * Catatan querki:
 *   - Manager list hanya menampilkan nama/date (bukan reason) → search
 *     `#team-approval-search` dipakai untuk menyaring card milik kita
 *     (semua query service match reason). Aman dari seeded pending lain.
 *   - Koreksi absensi 2 level: manager approve → status pending_admin (card
 *     TETAP tampil di tab manager, aksi masih bisa di-klik) → admin/HR approve
 *     final di /admin/attendance-corrections (default filter pending_admin).
 *     Jadi langkah L2 sekaligus membuktikan L1 berhasil (bila L1 silent-return,
 *     koreksi tetap 'pending' dan tidak muncul di daftar admin).
 */

const BASE = 'http://localhost:8000';
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const authDir = path.join(__dirname, '.auth');

const employeeState = path.join(authDir, 'employee.json');
const managerState = path.join(authDir, 'manager.json');
const hrState = path.join(authDir, 'hr.json');

test.describe.configure({ mode: 'serial' });

// ── helpers ────────────────────────────────────────────────────────────────

// Aplikasi ber-locale Indonesia — label (aria-label, teks status, nama tombol)
// diterjemahkan via __() sehingga selector E2E memakai atribut netral bahasa:
// wire:click / id, dan regex status /disetujui|approved/i.

// Input tanggal readonly + flatpickr: set value ISO langsung + dispatch,
// Livewire membaca value input. (Pola sama dgn workflow-modules.spec.ts.)
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

// Tanggal kerja (weekday) N hari ke depan (untuk tanggal yg butuh min=today).
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

// Manager approve di /approvals: pindah tab → search marker → klik approve.
// `expectRemoved=false` utk koreksi absensi (L1 → pending_admin, card masih tampil).
async function managerApproveBySearch(
    page: Page,
    tabLabel: RegExp,
    marker: string,
    expectRemoved = true,
): Promise<void> {
    await page.goto(`${BASE}/approvals`, { waitUntil: 'domcontentloaded', timeout: 20000 });
    const tab = page.locator('.team-approval-tab', { hasText: tabLabel });
    if (await tab.count()) {
        await tab.click();
    }
    await page.locator('#team-approval-search').fill(marker);
    const card = page.locator('.team-approval-card', { hasText: marker }).first();
    await expect(card).toBeVisible({ timeout: 30000 });
    await card.locator('.team-approval-action--approve').click();
    if (expectRemoved) {
        await expect(page.locator('.team-approval-card', { hasText: marker })).toHaveCount(0, { timeout: 30000 });
    } else {
        // L1 koreksi: status jadi pending_admin — tunggu re-render sebentar.
        await page.waitForTimeout(2000);
    }
}

// Digunakan utk cek token muncul di body (list employee).
async function findTextInBody(page: Page, text: string): Promise<boolean> {
    const body = await page.locator('body').innerText().catch(() => '');
    return body.includes(text);
}

// x-forms.select di area user me-render TomSelect (x-user.tom-select-user) —
// pilih via UI dropdown (.ts-control/.ts-dropdown), bukan selectOption native.
// Dropdown dirender ke <body> (dropdownParent fix 2026-09-05) → opsi di-locate
// page-level, bukan di dalam wrapper.
async function pickTomSelect(page: Page, selectId: string, optionText: string): Promise<void> {
    const wrapper = page.locator(`[data-ui-tomselect-root]:has(#${selectId})`);
    await expect
        .poll(() => page.evaluate((id) => !!document.getElementById(id)?.tomselect, selectId))
        .toBe(true);
    // Cek nilai BUKAN keberadaan [data-value] — allowEmptyOption membuat
    // placeholder terpilih juga punya item [data-value] (value '').
    const value = await page.evaluate((id) => document.getElementById(id)?.tomselect?.getValue() ?? '', selectId);
    if (value === '') {
        await wrapper.locator('.ts-control').click();
        await page.waitForTimeout(300); // dropdown selesai buka sebelum klik opsi
        await page.locator('.ts-dropdown .option').filter({ visible: true, hasText: optionText }).first().click();
    }
}

// ============================================================================
// 1) WFH — submit → manager approve (FINAL; 1 level)
// ============================================================================
const WFH_REASON = `E2E WFH ${Date.now()}`;

test.describe('workflow WFH: employee submit', () => {
    test.use({ storageState: employeeState });

    test('submit WFH via /wfh-requests (modal)', async ({ page }) => {
        await page.goto(`${BASE}/wfh-requests`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        await page.locator('button[wire\\:click="create"]').first().click();
        await page.waitForSelector('#wfh-date', { timeout: 10000 });
        await setFutureWeekday(page, 'wfh-date', 1);
        await page.locator('#wfh-location').fill('Rumah (E2E)');
        await page.locator('#wfh-reason').fill(WFH_REASON);
        await page.locator('#wfh-reason').dispatchEvent('change');

        const form = page.locator('form[wire\\:submit\\.prevent="submit"]').first();
        await Promise.all([
            page.waitForResponse((r) => r.url().includes('/livewire/update') && r.ok(), { timeout: 30000 }),
            form.locator('button[type="submit"]').click(),
        ]);
        await expect(form).toBeHidden({ timeout: 15000 });
        // List menampilkan reason + status
        expect(await findTextInBody(page, WFH_REASON)).toBe(true);
    });
});

test.describe('workflow WFH: manager approve (L1 final)', () => {
    test.use({ storageState: managerState });

    test('manager approve WFH di /approvals', async ({ page }) => {
        await managerApproveBySearch(page, /WFH/i, WFH_REASON);
    });
});

test.describe('workflow WFH: verifikasi final', () => {
    test.use({ storageState: employeeState });

    test('pengajuan WFH berstatus Approved di daftar employee', async ({ page }) => {
        await page.goto(`${BASE}/wfh-requests`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        const item = page.locator('.wfh-request-item').filter({ hasText: WFH_REASON }).first();
        await expect(item).toBeVisible({ timeout: 10000 });
        await expect(item).toContainText(/disetujui|approved/i, { timeout: 5000 });
    });
});

// ============================================================================
// 2) TU KAR SHIFT — submit → manager approve (FINAL; 1 level)
// ============================================================================
const SWAP_REASON = `E2E tukar shift ${Date.now()}`;

test.describe('workflow tukar shift: employee submit', () => {
    test.use({ storageState: employeeState });

    test('submit tukar shift via /shift-swap-requests (modal)', async ({ page }) => {
        await page.goto(`${BASE}/shift-swap-requests`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        await page.locator('button[wire\\:click="create"]').first().click();
        await page.waitForSelector('#swap-schedule', { timeout: 10000 });

        // Pilih shift tujuan (bukan placeholder & bukan Office Hour) — Flexible
        // di-seed ShiftSeeder. Opsi[0] = placeholder "Pilih shift yang diminta".
        const optionTexts = await page.locator('#swap-shift option').allTextContents();
        const target = optionTexts.slice(1).find((t) => !/office hour/i.test(t.trim()));
        expect(target, 'harus ada shift alternatif selain Office Hour').toBeTruthy();

        await page.locator('#swap-reason').fill(SWAP_REASON);
        await page.locator('#swap-reason').dispatchEvent('change');

        // Tanggal kandidat JAUH (>30 hari): jadwal employee hanya ter-seed +14
        // hari, jadi tanggal ini TIDAK punya schedule → store() lewat cabang
        // "no current schedule" tanpa guard hasPending (satu pending per
        // schedule). Kalau pakai tanggal dalam jendela seed, run E2E berulang
        // yang terputus meninggalkan pending swap → tabrakan guard → flaky.
        let submitted = false;
        for (const offset of [30, 60, 90, 120]) {
            const jitter = Math.floor(Math.random() * 15);
            await setFutureWeekday(page, 'swap-schedule', offset + jitter);
            await page.waitForTimeout(1500); // snapshot + live validation

            await pickTomSelect(page, 'swap-shift', target.trim());
            // Tunggu round-trip wire:model.live requestedShiftId selesai sebelum
            // submit — tanpa ini store() bisa jalan saat sync masih in-flight.
            await page.waitForTimeout(1200);

            await Promise.all([
                page.waitForResponse((r) => r.url().includes('/livewire/update') && r.ok(), { timeout: 30000 }),
                page.locator('button[wire\\:click="store"]').click(),
            ]);
            await page.waitForTimeout(1500);

            submitted = await page.locator('button[wire\\:click="store"]').isHidden().catch(() => true);
            if (submitted) break;
        }
        expect(submitted, 'submit tukar shift harus berhasil pada salah satu kandidat tanggal').toBe(true);

        // Modal tertutup = sukses; reason dirender sr-only di tabel
        expect(await findTextInBody(page, SWAP_REASON)).toBe(true);
    });
});

test.describe('workflow tukar shift: manager approve', () => {
    test.use({ storageState: managerState });

    test('manager approve tukar shift di /approvals', async ({ page }) => {
        await managerApproveBySearch(page, /Tukar Shift|Shift Swap/i, SWAP_REASON);
    });
});

test.describe('workflow tukar shift: verifikasi final', () => {
    test.use({ storageState: employeeState });

    test('pengajuan tukar shift berstatus Approved di daftar employee', async ({ page }) => {
        await page.goto(`${BASE}/shift-swap-requests`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        const row = page.locator('tr').filter({ hasText: SWAP_REASON }).first();
        await expect(row).toBeVisible({ timeout: 10000 });
        await expect(row).toContainText(/disetujui|approved/i, { timeout: 5000 });
    });
});

// ============================================================================
// 3) KOREKSI ABSENSI — submit → manager L1 → admin/HR L2 → final
// ============================================================================
const CORR_REASON = `E2E koreksi absen ${Date.now()}`;

test.describe('workflow koreksi absensi: employee submit', () => {
    test.use({ storageState: employeeState });

    test('submit koreksi check-in via /attendance-corrections (modal)', async ({ page }) => {
        await page.goto(`${BASE}/attendance-corrections`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        await page.locator('button[wire\\:click="create"]').first().click();
        await page.waitForSelector('#attendance-date', { timeout: 10000 });

        // Tanggal kemarin (max = hari ini) — bebas dari record attendance apa pun
        const yesterday = await page.evaluate(() => {
            const d = new Date(Date.now() - 86400000);
            return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        });
        await setDateRaw(page, 'attendance-date', yesterday);

        // Enable "Requested Check In Time" (default waktu dari server, format Y-m-d H:i)
        await page.locator('input[wire\\:model\\.live="includeRequestedTimeIn"]').check();
        // Tunggu round-trip live update (default requestedTimeIn di-set server)
        await page.waitForTimeout(1200);

        await page.locator('#correction-reason').fill(CORR_REASON);
        await page.locator('#correction-reason').dispatchEvent('change');

        await Promise.all([
            page.waitForResponse((r) => r.url().includes('/livewire/update') && r.ok(), { timeout: 30000 }),
            page.locator('button[wire\\:click="save"]').click(),
        ]);
        await expect(page.locator('button[wire\\:click="save"]')).toBeHidden({ timeout: 15000 });
        expect(await findTextInBody(page, CORR_REASON)).toBe(true);
    });
});

test.describe('workflow koreksi absensi: manager L1', () => {
    test.use({ storageState: managerState });

    test('manager forward koreksi ke admin di /approvals (L1)', async ({ page }) => {
        // expectRemoved=false: setelah L1 status pending_admin, card tetap tampil
        await managerApproveBySearch(page, /Koreksi|Correction/i, CORR_REASON, false);
    });
});

test.describe('workflow koreksi absensi: admin/HR L2', () => {
    test.use({ storageState: hrState });

    test('HR approve final di /admin/attendance-corrections (L2)', async ({ page }) => {
        page.on('dialog', (d) => d.accept());
        await page.goto(`${BASE}/admin/attendance-corrections`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        // Default filter pending_admin — hanya item yang sudah di-forward L1 muncul.
        // Target baris DESKTOP (`tr`) — kartu mobile (`article`) display:none;
        // `.first()` dari 'tr, article' bisa jatuh ke article tersembunyi.
        await page.locator('#attendance-correction-search').fill(CORR_REASON);
        const row = page.locator('tr').filter({ hasText: CORR_REASON }).first();
        await expect(row).toBeVisible({ timeout: 30000 });
        await row.locator('button[wire\\:click^="approve"]').click();
        await expect(page.locator('tr, article').filter({ hasText: CORR_REASON })).toHaveCount(0, { timeout: 30000 });
    });
});

test.describe('workflow koreksi absensi: verifikasi final', () => {
    test.use({ storageState: employeeState });

    test('koreksi absensi berstatus Approved di daftar employee', async ({ page }) => {
        await page.goto(`${BASE}/attendance-corrections`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        // Baris desktop (tr) — kartu mobile (.user-list-card) display:none
        const card = page.locator('tr').filter({ hasText: CORR_REASON }).first();
        await expect(card).toBeVisible({ timeout: 10000 });
        await expect(card).toContainText(/disetujui|approved/i, { timeout: 5000 });
    });
});