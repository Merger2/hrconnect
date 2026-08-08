#!/usr/bin/env node
/**
 * Ukur performa halaman — HRConnect (2026-08-08).
 *
 * Metrik per halaman (via Performance Navigation/Resource Timing API):
 *   - TTFB            : responseStart - requestStart (ms) — waktu header pertama
 *   - DOMContentLoaded: domContentLoadedEventEnd - startTime (ms)
 *   - Load            : loadEventEnd - startTime (ms)
 *   - Transfer        : total transferSize semua resource (bytes)
 *   - Requests        : jumlah resource request
 *   - Top resources   : 5 resource terbesar (nama + bytes)
 *
 * Catatan:
 *   - Cache dinonaktifkan default (cold load konsisten, bukan bFCache).
 *   - 'load' (bukan 'networkidle') karena app membuka koneksi SSE yang
 *     membuat networkidle tidak pernah tercapai.
 *   - Service worker PWA di-BLOCK saat mengukur: generateSW meng-precache
 *     semua chunk di background setelah load (termasuk vendor-charts/maps yang
 *     lazy) — itu beban SW, bukan beban page load, dan mencemari angka transfer.
 *   - Koneksi SSE dihitung sebagai resource biasa; transferSize-nya kecil
 *     pada jeda ukur singkat.
 *   - Localhost dev (php -S) lebih lambat dari produksi php-fpm — angka ini
 *     untuk perbandingan relatif antar perubahan, bukan absolut produksi.
 *
 * Usage:
 *   node scripts/measure-perf.mjs                                  # halaman default (employee)
 *   node scripts/measure-perf.mjs --pages /home,/payroll,/scan     # halaman spesifik
 *   node scripts/measure-perf.mjs --guest                          # + /login tanpa auth (fresh)
 *   node scripts/measure-perf.mjs --repeat 3                       # rata-rata 3 run per halaman
 *   node scripts/measure-perf.mjs --cpu 4 --latency 100            # throttle (simulasi mobile)
 *   node scripts/measure-perf.mjs --json                           # output JSON saja
 *   node scripts/measure-perf.mjs --save perf-baseline.json        # simpan hasil
 *   node scripts/measure-perf.mjs --compare perf-baseline.json     # delta vs hasil sebelumnya
 */

import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.PERF_BASE_URL || 'http://localhost:8000';
const STORAGE_STATE = 'tests/e2e/.auth/employee.json';

// ---------- argumen ----------
const args = process.argv.slice(2);
const getArg = (name, def = null) => {
    const i = args.indexOf(name);
    return i === -1 ? def : args[i + 1];
};
const hasFlag = (name) => args.includes(name);

const pagesArg = getArg('--pages');
const pages = pagesArg ? pagesArg.split(',').map((p) => p.trim()) : ['/home', '/attendance-history', '/payroll', '/knowledge-base/chat'];
const withLogin = hasFlag('--guest');
const repeat = Math.min(parseInt(getArg('--repeat', '1'), 10) || 1, 5);
const cpuThrottle = parseInt(getArg('--cpu', '0'), 10) || 0;
const latencyMs = parseInt(getArg('--latency', '0'), 10) || 0;
const jsonOnly = hasFlag('--json');
const savePath = getArg('--save');
const comparePath = getArg('--compare');

// ---------- throttle via CDP ----------
async function applyThrottle(page, { cpu, latency }) {
    const cdp = await page.context().newCDPSession(page);
    await cdp.send('Network.enable');
    await cdp.send('Network.emulateNetworkConditions', {
        offline: false,
        latency,
        downloadThroughput: -1,
        uploadThroughput: -1,
    });
    if (cpu > 0) {
        await cdp.send('Emulation.setCPUThrottlingRate', { rate: cpu });
    }
}

async function measurePage(page, pagePath) {
    // Baca timing SEBELUM navigasi berikutnya (performance entries reset per doc)
    return page.evaluate(() => {
        const nav = performance.getEntriesByType('navigation')[0];
        const res = performance.getEntriesByType('resource');

        const ttfb = nav ? nav.responseStart - nav.requestStart : -1;
        const dom = nav ? nav.domContentLoadedEventEnd - nav.startTime : -1;
        const load = nav ? nav.loadEventEnd - nav.startTime : -1;

        const byType = {};
        let transfer = 0;
        let transferInit = 0;
        const loadEnd = nav ? nav.loadEventEnd : 0;
        for (const r of res) {
            byType[r.initiatorType] = (byType[r.initiatorType] || 0) + 1;
            if (r.transferSize > 0) transfer += r.transferSize;
            // Beban "init" = resource yang MULAI sebelum load selesai. Prefetch
            // Laravel Vite (JetstreamServiceProvider: Vite::prefetch) men-download
            // SEMUA chunk manifest SETELAH load — itu beban bandwidth, bukan beban
            // render halaman. Dua metrik: init (render) vs total (bandwidth).
            if (r.transferSize > 0 && r.startTime < loadEnd) transferInit += r.transferSize;
        }

        const top = res
            .filter((r) => r.transferSize > 0)
            .sort((a, b) => b.transferSize - a.transferSize)
            .slice(0, 5)
            .map((r) => ({
                name: r.name.replace(/^https?:\/\/[^/]+/, ''),
                bytes: r.transferSize,
            }));

        return { ttfb, dom, load, transfer, transferInit, requests: res.length, byType, top };
    });
}

async function run() {
    const results = { run_at: new Date().toISOString(), base_url: BASE, throttle: { cpu: cpuThrottle, latency_ms: latencyMs }, pages: {} };
    const browser = await chromium.launch();
    const ctx = await browser.newContext({
        viewport: { width: 1280, height: 900 },
        storageState: fs.existsSync(STORAGE_STATE) ? STORAGE_STATE : undefined,
    });
    // Cold load: cache-buster query per navigasi (version-agnostic —
    // setCacheEnabled tidak tersedia di versi playwright terpasang).
    const bustUrl = (p) => BASE + p + (p.includes('?') ? '&' : '?') + '_perfbust=' + Date.now();

    // Blokir registrasi service worker (precache PWA mencemari angka transfer):
    // SW terdaftar di /build/sw.js dari layouts/app.blade.php + guest-layout.
    // Route abort tidak cukup (SW tetap bisa diregistrasi lalu fetch chunk
    // via cache), jadi kita stub navigator.serviceWorker.register secara total.
    await ctx.route('**/build/sw.js', (r) => r.abort());
    await ctx.addInitScript(() => {
        try {
            Object.defineProperty(navigator, 'serviceWorker', {
                value: {
                    register: () => Promise.resolve({}),
                    ready: Promise.resolve({}),
                    getController: () => null,
                },
                configurable: true,
            });
        } catch {
            // di beberapa runtime navigator.serviceWorker read-only — abaikan
        }
    });

    const targets = [...pages.map((p) => ({ path: p, guest: false }))];
    if (withLogin) targets.push({ path: '/login', guest: true });

    // Context kedua TANPA storageState untuk halaman guest (/login) — kalau
    // pakai state employee, /login malah redirect ke /home.
    let guestCtx = null;
    if (withLogin) {
        guestCtx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    }

    for (const { path: pagePath, guest } of targets) {
        const page = guest ? await guestCtx.newPage() : await ctx.newPage();
        await applyThrottle(page, { cpu: cpuThrottle, latency: latencyMs });

        const samples = [];
        for (let i = 0; i < repeat; i++) {
            await page.goto(bustUrl(pagePath), { waitUntil: 'load', timeout: 60000 }).catch((e) => {
                console.error(`GAGAL load ${pagePath}: ${e.message}`);
            });
            // Biarkan resource lambat (font, chunk lazy) selesai sebelum ukur
            await page.waitForTimeout(800);
            samples.push(await measurePage(page, pagePath));
        }
        await page.close();

        // rata-rata
        const avg = (key) => Math.round(samples.reduce((s, x) => s + (x[key] > 0 ? x[key] : 0), 0) / samples.length);
        results.pages[pagePath] = {
            ttfb_ms: avg('ttfb'),
            dom_ms: avg('dom'),
            load_ms: avg('load'),
            transfer_bytes: Math.round(samples.reduce((s, x) => s + x.transfer, 0) / samples.length),
            transfer_init_bytes: Math.round(samples.reduce((s, x) => s + x.transferInit, 0) / samples.length),
            requests: Math.round(samples.reduce((s, x) => s + x.requests, 0) / samples.length),
            by_type: samples[0].byType,
            top: samples[0].top,
            runs: samples.length,
        };
    }

    if (guestCtx) await guestCtx.close();
    await browser.close();

    // ---------- output ----------
    if (jsonOnly) {
        console.log(JSON.stringify(results, null, 2));
    } else {
        console.log(`Baseline performa @ ${results.run_at} — ${BASE} (cpu=${cpuThrottle}x, latency=${latencyMs}ms)`);
        console.log('='.repeat(96));
        console.log(
            'Page'.padEnd(28) +
            'TTFB(ms)'.padStart(10) +
            'DOM(ms)'.padStart(10) +
            'Load(ms)'.padStart(10) +
            'Init'.padStart(12) +
            'Total'.padStart(12) +
            'Req'.padStart(7)
        );
        console.log('-'.repeat(96));
        for (const [p, m] of Object.entries(results.pages)) {
            console.log(
                p.padEnd(28) +
                String(m.ttfb_ms).padStart(10) +
                String(m.dom_ms).padStart(10) +
                String(m.load_ms).padStart(10) +
                formatBytes(m.transfer_init_bytes).padStart(12) +
                formatBytes(m.transfer_bytes).padStart(12) +
                String(m.requests).padStart(7)
            );
        }
        console.log('='.repeat(96));
        console.log('Init = beban render (resource selesai sebelum load) — ini yang mempengaruhi Lighthouse.');
        console.log('Total = semua transfer termasuk prefetch Laravel Vite pasca-load (JetstreamServiceProvider).');
        console.log('Selisih besar Init↔Total = chunk lazy di-prefetch boros bandwidth (temuan optimasi).');
        for (const [p, m] of Object.entries(results.pages)) {
            console.log(`\n[${p}] top resource:`);
            for (const r of m.top) {
                console.log(`   ${formatBytes(r.bytes).padStart(10)}  ${r.name}`);
            }
        }
    }

    if (savePath) {
        fs.writeFileSync(path.resolve(savePath), JSON.stringify(results, null, 2));
        if (!jsonOnly) console.log(`\n💾 Tersimpan: ${savePath}`);
    }

    if (comparePath && fs.existsSync(comparePath)) {
        const prev = JSON.parse(fs.readFileSync(comparePath, 'utf8'));
        console.log(`\n📊 Delta vs ${comparePath} (${prev.run_at}):`);
        console.log('Page'.padEnd(28) + 'TTFB'.padStart(12) + 'DOM'.padStart(12) + 'Load'.padStart(12) + 'Transfer'.padStart(14) + 'Req'.padStart(8));
        console.log('-'.repeat(86));
        for (const [p, m] of Object.entries(results.pages)) {
            const o = prev.pages?.[p];
            if (!o) continue;
            const d = (k) => {
                const diff = m[k + '_ms'] - o[k + '_ms'];
                const pct = o[k + '_ms'] > 0 ? ((diff / o[k + '_ms']) * 100).toFixed(0) : '?';
                return `${diff >= 0 ? '+' : ''}${diff}ms (${pct}%)`;
            };
            const dB = m.transfer_bytes - o.transfer_bytes;
            const dR = m.requests - o.requests;
            console.log(
                p.padEnd(28) +
                d('ttfb').padStart(12) +
                d('dom').padStart(12) +
                d('load').padStart(12) +
                `${dB >= 0 ? '+' : ''}${formatBytes(dB)}`.padStart(14) +
                `${dR >= 0 ? '+' : ''}${dR}`.padStart(8)
            );
        }
        console.log('Hijau = lebih lambat dari sebelumnya (perbaiki), merah = lebih cepat. (angka = selisih & %)');
    }
}

function formatBytes(bytes) {
    if (bytes >= 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return bytes + ' B';
}

run().catch((e) => {
    console.error('FATAL:', e);
    process.exit(1);
});
