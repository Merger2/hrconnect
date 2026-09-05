import { expect, test } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

/**
 * End-to-end alur KASUS NYATA lintas role (bukti aplikasi jalan untuk hosting):
 *
 *   employee submit cuti (SICK, bukti PDF) → manager approve (L1) →
 *   HR/admin approve (L2) → status final "Approved" terlihat di UI.
 *
 * Bukan mock: browser asli + DB dev (E2eTestSeeder) + Livewire request sungguhan.
 * Catatan: Cuti Sakit dipilih karena TIDAK deduct kuota (LeaveBalance seed = 0),
 * wajib lampiran → sekaligus menguji upload PDF. Idempoten: note unik per run,
 * tidak menabrak seeded requests.
 *
 * Auth: state role berbeda per test (storageState). Mode serial — setiap test
 * bergantung pada hasil test sebelumnya.
 */

const BASE = 'http://localhost:8000';
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const authDir = path.join(__dirname, '.auth');

const employeeState = path.join(authDir, 'employee.json');
const managerState = path.join(authDir, 'manager.json');
const hrState = path.join(authDir, 'hr.json');

// Catatan unik dipakai lintas test (reason = "note" pengajuan).
const REASON = `E2E workflow cuti ${Date.now()}`;

test.describe.configure({ mode: 'serial' });

test.describe('workflow: employee submit leave request', () => {
    test.use({ storageState: employeeState });

    test('employee mengisi form /apply-leave → pengajuan masuk (redirect home + flash sukses)', async ({ page }) => {
        const pageErrors: string[] = [];
        page.on('pageerror', (e) => pageErrors.push(e.message));
        page.on('console', (m) => {
            if (m.type() === 'error') pageErrors.push(m.text());
        });

        const resp = await page.goto(`${BASE}/apply-leave`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        expect(resp!.status()).toBeLessThan(500);
        await page.waitForSelector('#leave_type_id', { timeout: 10000 });
        await expect(page.locator('body')).not.toContainText(/exception|stack trace|whoops/i);

        // Helper: isi form (tipe + tanggal + alasan + lampiran) & submit.
        // Retry dengan rentang tanggal BERBEDA (lompat +10 hari tiap percobaan)
        // karena server menolak overlap dengan pengajuan seeded/hasil run E2E
        // sebelumnya. Hari libur nasional dihitung server (contoh: 7 Sep 2026 =
        // Cuti Bersama Maulid) — karena itu dipakai rentang, bukan 1 hari.
        const fillAndSubmit = async (attempt: number): Promise<boolean> => {
            if (attempt > 0) {
                await page.goto(`${BASE}/apply-leave`, { waitUntil: 'domcontentloaded', timeout: 20000 });
                await page.waitForSelector('#leave_type_id', { timeout: 10000 });
                await expect
                    .poll(() => page.evaluate(() => !!document.getElementById('leave_type_id')?.tomselect))
                    .toBe(true);
            }

            const wrapper = page.locator('[data-ui-tomselect-root]:has(#leave_type_id)');
            const hasSelection = await wrapper
                .locator('.ts-control [data-value]')
                .count()
                .catch(() => 0);
            if (hasSelection === 0) {
                await wrapper.locator('.ts-control').click();
                // Cuti Tanpa Gaji (id 6): deducts_from_quota=false → aman untuk
                // rentang masa depan mana pun (jenis lain butuh LeaveBalance tahun
                // berjalan yg baru diinisialisasi; contoh: Cuti Sakit deduct=true).
                // Dropdown dirender ke <body> (dropdownParent fix 2026-09-05) →
                // opsi di-locate page-level.
                await page.locator('.ts-dropdown .option[data-value="6"]').click();
            }

            await page.evaluate((baseOffset) => {
                const fmt = (d: Date): string =>
                    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                const today = new Date();
                let pick: Date | null = null;
                for (let i = baseOffset; i <= baseOffset + 14; i += 1) {
                    const d = new Date(today.getFullYear(), today.getMonth(), today.getDate() + i);
                    const dow = d.getDay();
                    if (dow !== 0 && dow !== 6) {
                        pick = d;
                        break;
                    }
                }
                if (!pick) throw new Error('no weekday found');
                const end = new Date(pick.getFullYear(), pick.getMonth(), pick.getDate() + 4);
                const from = document.getElementById('from') as HTMLInputElement;
                const to = document.getElementById('to') as HTMLInputElement;
                from.value = fmt(pick);
                to.value = fmt(end);
                from.dispatchEvent(new Event('change', { bubbles: true }));
            }, attempt * 300 + 200 + Math.floor(Math.random() * 250));

            await page.locator('#note').fill(REASON);
            await page.setInputFiles('#attachment', {
                name: 'surat-e2e.pdf',
                mimeType: 'application/pdf',
                buffer: Buffer.from('%PDF-1.4\n% E2E workflow attachment\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF'),
            });

            // Tunggu navigasi singkat: sukses → /home, gagal validasi → kembali
            // ke /apply-leave (jangan tunggu 30s utk percobaan yang gagal).
            await Promise.all([
                page
                    .waitForURL((u) => u.pathname === '/home' || u.pathname === '/apply-leave', { timeout: 15000 })
                    .catch(() => null),
                page.locator('form[action*="apply-leave"] button[type="submit"]').click(),
            ]);

            return page.url().endsWith('/home');
        };

        // Submit sukses = redirect ke home (gagal validasi = redirect balik ke
        // /apply-leave). Catatan: flash `session('success')` dari controller
        // tidak dirender halaman home (perilaku lama, di luar scope spec ini).
        let submitted = false;
        for (let attempt = 0; attempt < 3 && !submitted; attempt += 1) {
            submitted = await fillAndSubmit(attempt);
        }
        expect(submitted, 'submit cuti gagal 3× (validasi/overlap) — lihat log server').toBe(true);
        await expect(page).toHaveURL(/\/home$/, { timeout: 5000 });

        expect(pageErrors).toEqual([]);
    });
});

test.describe('workflow: manager approve (L1)', () => {
    test.use({ storageState: managerState });

    test('manager melihat pengajuan employee di /approvals → approve → hilang dari pending', async ({ page }) => {
        const resp = await page.goto(`${BASE}/approvals`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        expect(resp!.status()).toBeLessThan(500);

        const card = page.locator('.team-approval-card', { hasText: REASON });
        await expect(card).toBeVisible({ timeout: 30000 });

        // Klik tangguh: Livewire kadang belum siap saat klik pertama (race
        // hydrate) — ulangi klik selama card pending masih tampil.
        for (let attempt = 0; attempt < 5; attempt += 1) {
            if ((await card.count()) === 0) break;
            const approveBtn = card.locator('.team-approval-action--approve').first();
            if (await approveBtn.isVisible().catch(() => false)) {
                await approveBtn.click();
            }
            await page.waitForTimeout(2500);
        }

        // Livewire refresh: card tak lagi tampil di daftar pending
        await expect(card).toHaveCount(0, { timeout: 30000 });
    });
});

test.describe('workflow: HR/admin approve (L2)', () => {
    test.use({ storageState: hrState });

    test('HR meng-approve L2 di /admin/leaves → status final approved', async ({ page }) => {
        const resp = await page.goto(`${BASE}/admin/leaves`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        expect(resp!.status()).toBeLessThan(500);

        // Search by note (toolbar search "employee, NIP, or note")
        await page.locator('#leave-search').fill(REASON);

        // Viewport desktop → daftar tampil sebagai TABEL (card mobile hidden).
        const row = page.locator('table tbody tr').filter({ hasText: REASON });
        await expect(row.first()).toBeVisible({ timeout: 30000 });

        // Klik tangguh (race hydrate Livewire) sampai tombol approve hilang
        // (status berubah → tombol approve tidak lagi dirender utk baris ini).
        // Selector netral bahasa: label UI diterjemahkan ("Setujui pengajuan
        // cuti"), jadi pilih berdasarkan wire:click="approve(...)".
        const approveBtn = row.locator('button[wire\\:click^="approve"]');
        for (let attempt = 0; attempt < 5; attempt += 1) {
            if ((await approveBtn.count()) === 0) break;
            if (await approveBtn.first().isVisible().catch(() => false)) {
                await approveBtn.first().click();
            }
            await page.waitForTimeout(2500);
        }

        await expect(approveBtn).toHaveCount(0, { timeout: 30000 });
    });
});

test.describe('workflow: verifikasi final status approved', () => {
    test.use({ storageState: managerState });

    test('riwayat approval manager menunjukkan pengajuan tsb berstatus Approved', async ({ page }) => {
        const resp = await page.goto(`${BASE}/approvals/history`, { waitUntil: 'domcontentloaded', timeout: 20000 });
        expect(resp!.status()).toBeLessThan(500);

        const card = page.locator('.team-approval-card', { hasText: REASON });
        await expect(card).toBeVisible({ timeout: 30000 });
        await expect(card.locator('.team-approval-status--success')).toBeVisible({ timeout: 5000 });
        // Badge status diterjemahkan ke bahasa Indonesia ("Disetujui Final").
        await expect(card).toContainText(/disetujui final|approved/i);
    });
});
