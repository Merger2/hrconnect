#!/usr/bin/env node
/**
 * Ekstrak critical CSS — HRConnect (2026-08-08, v2).
 *
 * Iterasi document.styleSheets (same-origin), kumpulkan STYLE RULE yang
 * selector-nya match elemen DOM (dua viewport: mobile 390px + desktop 1440px,
 * gabung hasilnya), DEDUPE PER-RULE dengan path (layer/media/supports), lalu
 * reassemble dalam blok @layer yang benar.
 *
 * Mengapa dedupe per-rule (bukan per-blok @layer): setiap halaman menghasilkan
 * blok `@layer components{...}` dengan isi berbeda — dedupe blok penuh
 * menyisakan N blok duplikat (60+36+34+29KB utk 4 halaman). Dedupe per-rule
 * memangkas duplikasi lintas halaman.
 *
 * Mengapa pertahankan @layer: app.css pakai @layer theme/base/components/
 * utilities — menghapus wrapper layer mengubah urutan cascade (un-layered
 * menang atas layered). Urutan layer output: theme, base, components,
 * utilities, properties, lalu sisanya urutan kemunculan.
 *
 * Mengapa JANGAN strip :root/pseudo-class: :root = definisi semua CSS
 * variables (--color-*, --font-*) — tanpa :root seluruh critical CSS mati
 * (var() kosong → fallback browser). Hanya pseudo-ELEMENT (::before dll)
 * yang THROW di querySelector — strip hanya sebagai fallback saat selector
 * asli gagal. :hover dll tidak throw — tinggal return null (rule di-drop,
 * acceptable utk critical path).
 *
 * Output: file CSS minified (inline di <style> layout, full CSS di-defer
 * async via media="print" onload).
 *
 * Usage:
 *   node scripts/extract-critical-css.mjs \
 *     --urls /login,/home,/attendance-history,/payroll,/knowledge-base/chat,/admin \
 *     --state tests/e2e/.auth/employee.json \
 *     --out resources/css/critical/critical-app.css
 *   node scripts/extract-critical-css.mjs --urls /login --guest --out resources/css/critical/critical-guest.css
 */

import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.PERF_BASE_URL || 'http://localhost:8000';
const args = process.argv.slice(2);
const getArg = (name, def = null) => {
    const i = args.indexOf(name);
    return i === -1 ? def : args[i + 1];
};
const urls = (getArg('--urls') || '/login').split(',').map((u) => u.trim());
const state = getArg('--state');
const guest = args.includes('--guest');
const out = getArg('--out', '/tmp/critical.css');

const VIEWPORTS = [
    { width: 390, height: 844 },
    { width: 1440, height: 900 },
];

function minify(css) {
    return css
        .replace(/\/\*[\s\S]*?\*\//g, '')
        .replace(/\s+/g, ' ')
        .replace(/\s*([{}:;,])\s*/g, '$1')
        .trim();
}

async function run() {
    const browser = await chromium.launch();
    // ruleKey -> { layer, media, supports, css } ; keyframes/font-face terpisah
    const ruleMap = new Map();
    const keyframes = new Set();
    const fontFaces = new Set();

    for (const vp of VIEWPORTS) {
        const ctx = await browser.newContext({
            viewport: vp,
            storageState: !guest && fs.existsSync(state) ? state : undefined,
        });
        for (const url of urls) {
            const page = await ctx.newPage();
            try {
                await page.goto(BASE + url, { waitUntil: 'load', timeout: 45000 });
                await page.waitForTimeout(600);
                const collected = await page.evaluate(() => {
                    const stripPseudoElements = (sel) => sel
                        .replace(/::[a-zA-Z-]+(\([^)]*\))?/g, '')
                        .replace(/:(before|after|first-line|first-letter|placeholder|selection|marker|backdrop)(\([^)]*\))?/gi, '')
                        .replace(/\s+/g, ' ')
                        .trim();
                    const testSel = (sel) => {
                        if (!sel) return false;
                        try {
                            if (document.querySelector(sel)) return true;
                        } catch {
                            try {
                                const s2 = stripPseudoElements(sel);
                                if (s2 && s2 !== sel && document.querySelector(s2)) return true;
                            } catch { /* ignore */ }
                        }
                        return false;
                    };
                    const is = (r, name) => r.constructor.name === name;
                    const out = { rules: [], keyframes: [], fontFaces: [] };

                    const walk = (containerRule, ctx) => {
                        let rules;
                        try {
                            rules = containerRule.cssRules;
                        } catch {
                            return;
                        }
                        if (!rules) return;
                        for (const r of rules) {
                            try {
                                if (is(r, 'CSSStyleRule')) {
                                    if (testSel(r.selectorText)) {
                                        out.rules.push({ ...ctx, css: r.cssText });
                                    }
                                } else if (is(r, 'CSSMediaRule')) {
                                    walk(r, { ...ctx, media: r.conditionText });
                                } else if (is(r, 'CSSSupportsRule')) {
                                    walk(r, { ...ctx, supports: r.conditionText });
                                } else if (is(r, 'CSSLayerBlockRule')) {
                                    walk(r, { ...ctx, layer: r.name || ctx.layer });
                                } else if (is(r, 'CSSKeyframesRule')) {
                                    out.keyframes.push(r.cssText);
                                } else if (is(r, 'CSSFontFaceRule')) {
                                    const src = r.style.getPropertyValue('src') || '';
                                    if (!src.includes('fonts.gstatic') && !src.includes('fonts.googleapis')) {
                                        out.fontFaces.push(r.cssText);
                                    }
                                }
                                // IMPORT_RULE / LAYER_STATEMENT_RULE di-skip
                            } catch { /* ignore individual rule */ }
                        }
                    };

                    for (const sheet of document.styleSheets) {
                        const href = sheet.href || '';
                        if (href && !href.startsWith(location.origin)) continue;
                        walk(sheet, { layer: null, media: null, supports: null });
                    }
                    return out;
                });

                for (const r of collected.rules) {
                    ruleMap.set(`${r.layer}|${r.media}|${r.supports}|${r.css}`, r);
                }
                for (const k of collected.keyframes) keyframes.add(k);
                for (const f of collected.fontFaces) fontFaces.add(f);

                console.log(`  [${vp.width}px] ${url}: ${collected.rules.length} rules, ${collected.keyframes.length} keyframes`);
            } catch (e) {
                console.error(`  GAGAL ${url}: ${e.message}`);
            }
            await page.close();
        }
        await ctx.close();
    }

    // Assemble — JANGAN re-sort: urutan cascade dalam layer ditentukan urutan
    // penulisan. Walk order = urutan stylesheet asli (deterministik antar
    // halaman: halaman berikutnya hanya menambah rule yang belum match, tetap
    // dalam urutan walk yang sama). Emit tiap rule sebagai blok kecil sendiri
    // (wrapper layer/media/supports) dalam urutan first-seen — layer order
    // tetap benar (theme/base/components/utilities sesuai kemunculan pertama),
    // dan urutan antar-rule dalam satu layer terjaga.
    const parts = [];
    for (const f of fontFaces) parts.push(f);
    for (const k of keyframes) parts.push(k);
    for (const r of ruleMap.values()) {
        let css = r.css;
        if (r.supports) css = `@supports ${r.supports}{${css}}`;
        if (r.media) css = `@media ${r.media}{${css}}`;
        if (r.layer) css = `@layer ${r.layer}{${css}}`;
        parts.push(css);
    }

    const combined = parts.join('\n');
    const minified = minify(combined);
    const bytes = Buffer.byteLength(minified, 'utf8');

    fs.mkdirSync(path.dirname(out), { recursive: true });
    fs.writeFileSync(out, minified);
    console.log(`\n✅ ${ruleMap.size} unique style rules, ${(bytes / 1024).toFixed(1)} KB minified`);
    console.log(`   ${out}`);
    await browser.close();
}

run().catch((e) => {
    console.error('FATAL:', e);
    process.exit(1);
});
