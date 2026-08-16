#!/usr/bin/env node
/**
 * check-flatpickr-render.mjs — Regression check rendering flatpickr kalender.
 *
 * Self-contained: TIDAK butuh halaman temp di public/ dan TIDAK butuh login.
 * Cara kerja:
 *  1. Buka halaman nyata dev server (origin sama) → app.js + semua asset CSS
 *     build termuat otomatis (tanpa masalah CORS yang muncul kalau pakai
 *     page.setContent — origin 'null' diblokir oleh asset server).
 *  2. Suntik 3 input test (single date, date-range, data-ui-picker-static)
 *     ke dalam DOM, lalu panggil window.initUiPickers() → initFlatpickr asli
 *     dari production.
 *  3. Ukur geometri kalender via getBoundingClientRect.
 *
 * Yang dicek (regresi yang pernah terjadi):
 *  1. Tiap picker ter-init + kalender terbuka saat diklik.
 *  2. Grid hari: 5-6 baris x 7 kolom — BUKAN satu baris menyempit
 *     (bug: base flatpickr.css tidak di-load → flex-wrap hilang) dan
 *     BUKAN 8 kolom (bug: max-width sel 36px + kalender lebar 352px →
 *     muat 8 kolom, hari tidak sejajar dengan header weekday).
 *     Root test sengaja lebar (460px) supaya kalender selalu 352px,
 *     jalur yang memicu regresi 8-kolom itu.
 *  3. Header weekday: 1 baris rapi, 7 kolom.
 *  4. Panah prev/next: kecil (<40px), di dalam kalender, di baris header
 *     (bug: base css hilang → panah raksasa nyeberang layar).
 *  5. Dropdown bulan + input tahun hadir di header (bukan melayang).
 *  6. Kalender tepat di bawah input (anchor ke wrapper).
 *  7. Value input terformat 'd M Y' (range: 'd M Y to d M Y' — separator
 *     default flatpickr); input data-ui-picker-static (type="date")
 *     terkonversi ke text.
 *  8. Tidak ada uncaught page error.
 *
 * Prasyarat:
 *  - `npm run build` sudah dijalankan (asset fresh di public/build).
 *  - Dev server aktif: `php artisan serve` (default http://127.0.0.1:8000).
 *
 * Usage:
 *   node scripts/check-flatpickr-render.mjs
 *   node scripts/check-flatpickr-render.mjs --base-url http://127.0.0.1:8000 --viewport 932x902
 *   node scripts/check-flatpickr-render.mjs --screenshot /tmp/fp-fail.png   # simpan screenshot saat gagal
 *
 * Exit code 0 = semua check lulus, 1 = ada regresi.
 */
import { chromium } from 'playwright';

// ─── CLI args ────────────────────────────────────────────────────────────
const args = process.argv.slice(2);
const argValue = (name, def) => {
    const i = args.indexOf(name);
    return i >= 0 && args[i + 1] ? args[i + 1] : def;
};
const has = (name) => args.includes(name);
const baseUrl = argValue('--base-url', 'http://127.0.0.1:8000').replace(/\/$/, '');
const vp = argValue('--viewport', '932x902').split('x').map(Number);
if (vp.length !== 2 || vp.some(Number.isNaN) || vp.some((n) => n <= 0)) {
    console.error('--viewport harus format WxH (contoh: 932x902).');
    process.exit(1);
}
const viewport = { width: vp[0], height: vp[1] };
const screenshotPath = has('--screenshot') ? argValue('--screenshot', '/tmp/flatpickr-fail.png') : null;

if (has('--help') || has('-h')) {
    console.log('Gunakan: node scripts/check-flatpickr-render.mjs [--base-url URL] [--viewport WxH] [--screenshot PATH]');
    process.exit(0);
}

// ─── HTML field test yang disuntik ke DOM halaman dev server ─────────────
const TEST_FIELDS = `
<div id="fp-check-root" style="position:fixed;top:10px;left:10px;z-index:100000;background:#fff;padding:12px;border:1px solid #cbd5e1;border-radius:12px;width:460px;">
  <div style="font-weight:800;font-size:12px;margin-bottom:8px;color:#0f172a;">flatpickr render check</div>
  <label style="font-size:11px;font-weight:700;display:block;margin:6px 0 2px;">1. single</label>
  <input id="fp-d1" type="text" value="2026-08-06" style="width:100%;box-sizing:border-box;padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;" data-ui-picker="date" readonly>
  <label style="font-size:11px;font-weight:700;display:block;margin:6px 0 2px;">2. range</label>
  <input id="fp-d2" type="text" value="2026-08-06 - 2026-08-10" style="width:100%;box-sizing:border-box;padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;"
         data-ui-picker="date-range" data-ui-range-from="#fp-from" data-ui-range-to="#fp-to" readonly>
  <input type="hidden" id="fp-from" value="2026-08-06">
  <input type="hidden" id="fp-to" value="2026-08-10">
  <label style="font-size:11px;font-weight:700;display:block;margin:6px 0 2px;">3. static (type=date)</label>
  <input id="fp-d3" type="date" value="2026-08-06" style="width:100%;box-sizing:border-box;padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;" data-ui-picker-static="true">
</div>`;

const browser = await chromium.launch();
const page = await browser.newPage({ viewport });
const pageErrors = [];
const consoleErrors = [];
page.on('pageerror', (e) => pageErrors.push(String(e).slice(0, 200)));
page.on('console', (m) => { if (m.type() === 'error') consoleErrors.push(m.text().slice(0, 200)); });

// 1. Buka halaman nyata (origin sama dengan asset) — /login selalu ada di app ini.
try {
    await page.goto(baseUrl + '/login', { waitUntil: 'domcontentloaded', timeout: 15000 });
} catch (e) {
    console.error('✗ Tidak bisa membuka ' + baseUrl + '/login. Pastikan dev server aktif (php artisan serve).');
    console.error('  ', String(e).slice(0, 200));
    await browser.close();
    process.exit(1);
}

// 2. Tunggu app.js module selesai (window.flatpickr + initUiPickers ter-set).
try {
    await page.waitForFunction(
        () => typeof window.flatpickr !== 'undefined' && typeof window.initUiPickers === 'function',
        null, { timeout: 15000 }
    );
} catch {
    console.error('✗ app.js module tidak termuat di ' + baseUrl + '. Cek hasil `npm run build` dan dev server.');
    await browser.close();
    process.exit(1);
}

// 3. Suntik field test + init.
await page.evaluate((html) => {
    const host = document.createElement('div');
    host.innerHTML = html;
    document.body.appendChild(host);
}, TEST_FIELDS);
await page.evaluate(() => window.initUiPickers());
// Timeout di sini sengaja di-swallow: kalau init gagal, check "kalender
// terbuka" per-picker yang melaporkannya (measure → found:false).
await page.waitForFunction(
    () => ['fp-d1', 'fp-d2', 'fp-d3'].every((id) => document.getElementById(id)?.hasAttribute('data-flatpickr-inited')),
    null, { timeout: 5000 }
).catch(() => {});

// ─── Ukur geometri kalender ──────────────────────────────────────────────
const measure = (id) => page.evaluate((id) => {
    const input = document.getElementById(id);
    if (!input?._flatpickr) return { found: false, id };
    input._flatpickr.open();
    return new Promise((resolve) => {
        // Settle dulu: open() memicu CSS transition (animate class) — ukur
        // geometri setelah posisi final stabil, hindari race mid-transition.
        setTimeout(() => resolve(measureNow(input)), 50);
    });

    function measureNow(input) {
    const cal = input._flatpickr.calendarContainer || input.closest('.flatpickr-wrapper')?.querySelector('.flatpickr-calendar');
    const box = (el) => {
        if (!el) return null;
        const b = el.getBoundingClientRect();
        return { x: Math.round(b.x), y: Math.round(b.y), w: Math.round(b.width), h: Math.round(b.height) };
    };
    const days = [...cal.querySelectorAll('.flatpickr-day')].map((d) => box(d));
    const weekdays = [...cal.querySelectorAll('.flatpickr-weekday')].map((d) => box(d));
    const inputBox = box(input);
    return {
        found: true,
        calendar: box(cal),
        input: inputBox,
        dayRows: [...new Set(days.map((d) => d.y))].length,
        daysPerFirstRow: days.length ? days.filter((d) => d.y === days[0].y).length : 0,
        totalDays: days.length,
        weekdayCount: weekdays.length,
        weekdayRows: [...new Set(weekdays.map((w) => w.y))].length,
        prev: box(cal.querySelector('.flatpickr-prev-month')),
        next: box(cal.querySelector('.flatpickr-next-month')),
        monthDrop: box(cal.querySelector('.flatpickr-monthDropdown-months')),
        yearInput: box(cal.querySelector('.cur-year')),
        inputValue: input.value,
        inputType: input.type,
    };
    }
}, id);

const checks = [];
const check = (name, ok, detail) => checks.push({ name, ok, detail: detail ?? '' });

const pickers = [
    { id: 'fp-d1', label: 'single', expectValue: /^\d{2} \w{3} \d{4}$/ },
    { id: 'fp-d2', label: 'range', expectRange: true },
    { id: 'fp-d3', label: 'static', expectValue: /^\d{2} \w{3} \d{4}$/, expectType: 'text' },
];

for (const p of pickers) {
    const m = await measure(p.id);
    const tag = `[${p.label}]`;

    check(`${tag} kalender terbuka`, m.found, m.found ? '' : 'flatpickr tidak ter-init');
    if (!m.found) continue;

    check(`${tag} grid hari 5-6 baris (bukan 1 baris menyempit)`, m.dayRows >= 5, `rows=${m.dayRows}`);
    check(`${tag} 7 kolom per baris`, m.daysPerFirstRow === 7, `cols=${m.daysPerFirstRow}`);
    check(`${tag} header weekday 1 baris x 7`, m.weekdayRows === 1 && m.weekdayCount === 7, `rows=${m.weekdayRows} count=${m.weekdayCount}`);
    check(`${tag} panah prev/next kecil & di dalam kalender`,
        !!m.prev && !!m.next && m.prev.w <= 40 && m.next.w <= 40
        && m.prev.x >= m.calendar.x && m.next.x + m.next.w <= m.calendar.x + m.calendar.w
        && m.prev.y >= m.calendar.y && m.prev.y <= m.calendar.y + 60,
        m.prev && m.next ? `prev=${m.prev.w}x${m.prev.h} next=${m.next.w}x${m.next.h}` : 'missing');
    check(`${tag} dropdown bulan + input tahun di header`, !!m.monthDrop && !!m.yearInput);
    // Kalender static diposisikan tepat di bawah input; tepi atasnya bisa
    // overlap ±14px karena panah (arrowTop). Terima toleransi 40px.
    check(`${tag} kalender tepat di bawah input (anchor)`, m.input && Math.abs(m.calendar.y - (m.input.y + m.input.h)) <= 40,
        m.input ? `input.bottom=${m.input.y + m.input.h} cal.y=${m.calendar.y}` : 'no input');

    if (p.expectRange) {
        const v = m.inputValue || '';
        // Separator range flatpickr default = " to ", bukan " - ".
        check(`${tag} value terformat 'd M Y to d M Y'`, /^\d{2} \w{3} \d{4} to \d{2} \w{3} \d{4}$/.test(v), `value="${v}"`);
        const from = await page.$eval('#fp-from', (el) => el.value);
        const to = await page.$eval('#fp-to', (el) => el.value);
        check(`${tag} hidden from/to tetap Y-m-d (6/10 Agu)`, from === '2026-08-06' && to === '2026-08-10', `from=${from} to=${to}`);
    } else {
        check(`${tag} value terformat 'd M Y'`, p.expectValue.test(m.inputValue || ''), `value="${m.inputValue}"`);
    }
    if (p.expectType) {
        check(`${tag} input type dikonversi ke text`, m.inputType === p.expectType, `type=${m.inputType}`);
    }
    await page.evaluate((id) => document.getElementById(id)?._flatpickr?.close(), p.id);
    await page.waitForTimeout(120);
}

check('tidak ada uncaught page error', pageErrors.length === 0, pageErrors.join(' | '));

// ─── Laporan ─────────────────────────────────────────────────────────────
let failed = 0;
console.log('\n=== HASIL CHECK FLATPICKR RENDER (' + baseUrl + ') ===');
for (const c of checks) {
    const mark = c.ok ? 'PASS' : 'FAIL';
    if (!c.ok) failed++;
    console.log(`  ${mark}  ${c.name}${c.detail ? '  (' + c.detail + ')' : ''}`);
}
if (consoleErrors.length) {
    console.log('\n⚠ console errors (' + consoleErrors.length + ', tidak menggagalkan):');
    for (const e of consoleErrors.slice(0, 5)) console.log('   ', e);
}
if (failed === 0) {
    console.log('\n✅ SEMUA CHECK LULUS — kalender flatpickr render normal.');
} else {
    console.log(`\n❌ ${failed} CHECK GAGAL — regresi render flatpickr.`);
    if (screenshotPath) {
        await page.screenshot({ path: screenshotPath });
        console.log('   screenshot: ' + screenshotPath);
    }
}
await browser.close();
process.exit(failed === 0 ? 0 : 1);
