# Design — HRConnect

A locked design system for HRConnect HRIS. Every page redesign reads this file first.
Do not regenerate per page — extend or amend this file when the system needs to grow.

## Genre
modern-minimal

## Macrostructure family
- Marketing pages (login, landing): **Marquee Hero** — centered narrow hero, off-axis CTA/eyebrow, single visual anchor
- App pages (dashboard, admin, employee, attendance, payroll, finance): **Workbench** — dense toolbar + content canvas, persistent sidebar/bottom-nav, high information density
- Content pages (settings, profile, help, knowledge-base): **Long Document** — generous measure, clear heading rhythm, minimal chrome

## Theme (OKLCH — Light mode ONLY)
--color-paper:      oklch(100% 0 0);           /* #FFFFFF - pure white canvas */
--color-paper-2:    oklch(98.5% 0.003 240);    /* #F7F9FC - subtle blue-tint surface */
--color-paper-3:    oklch(94% 0.006 240);      /* #EDF2F7 - elevated surface */
--color-ink:        oklch(20% 0.02 255);       /* #1A202C - primary text */
--color-ink-2:      oklch(45% 0.02 255);       /* #718096 - secondary text */
--color-rule:       oklch(88% 0.01 240);       /* #E2E8F0 - borders, dividers */
--color-accent:     oklch(58% 0.22 260);       /* #3B82F6 - brand blue (primary) */
--color-accent-ink: oklch(100% 0 0);           /* #FFFFFF - text on accent */
--color-focus:      oklch(58% 0.22 260);       /* matches accent for focus ring */
--color-success:    oklch(60% 0.18 150);       /* #10B981 */
--color-warning:    oklch(75% 0.18 85);        /* #F59E0B */
--color-error:      oklch(58% 0.22 25);        /* #EF4444 */

## Modular Hue System (2026-08-05 — colorful design decision)

Every module carries its own accent hue so users can navigate by color. The
global brand blue stays for chrome/CTAs; module hues appear on icons, badges,
tabs, section headers, and small accent strips (≤8% of viewport per module page).

| Modul | Hue token | OKLCH | Notes |
|-------|-----------|-------|-------|
| HR & Master Data | `--color-module-hr` | oklch(58% 0.22 260) | = brand blue (default) |
| Absensi & Jadwal | `--color-module-attendance` | oklch(70% 0.14 200) | cyan/teal |
| Cuti & Approval | `--color-module-leave` | oklch(60% 0.20 295) | violet |
| Payroll & Payslip | `--color-module-payroll` | oklch(65% 0.17 160) | emerald |
| Dokumen & Checklist | `--color-module-documents` | oklch(70% 0.17 75) | amber |
| Reports & Import/Export | `--color-module-reports` | oklch(60% 0.18 230) | sky blue |
| AI Knowledge Base | `--color-module-kb` | oklch(60% 0.22 330) | fuchsia |
| Finance (reimburse/kasbon) | `--color-module-finance` | oklch(65% 0.20 20) | rose/coral |

Rules:
- CTA, primary buttons, focus rings, active nav = **global accent** (brand blue), never a module hue.
- Module hue appears on: module icons, status badges, tab underline, section accent strip, table header tint.
- Gradient accents allowed ONLY as a subtle module-tint wash on page header surfaces (paper-2 → module hue at ≤10% alpha). No rainbow mixing on one page.
- Success/warning/error semantic colors unchanged; a module page uses at most its own hue + semantic colors.
- Dark mode: not supported (light-only, per user decision 2026-08-05).

## Typography
- Display: Rubik, Inter, sans-serif — weight 600, style normal
- Body:    Inter, system-ui, sans-serif — weight 400/500/600
- Mono:    Monaco, Consolas, monospace — for code/technical
- Display tracking: -0.02em (tight)
- Type scale anchor: --text-display = clamp(2.25rem, 4vw + 1rem, 3.5rem)

## Spacing
4-point named scale (Tailwind-compatible):
--space-3xs: 0.25rem (4px)  --space-2xs: 0.5rem (8px)  --space-xs: 0.75rem (12px)
--space-sm:  1rem (16px)     --space-md:  1.5rem (24px) --space-lg: 2rem (32px)
--space-xl:  3rem (48px)     --space-2xl: 4.5rem (72px) --space-3xl: 7rem (112px)

## Motion
- Easings: --ease-out: cubic-bezier(0.16, 1, 0.3, 1); --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1)
- Reveal pattern: fade + subtle slide (translateY 4px → 0) on mount; NO scroll reveals
- Reduced-motion fallback: opacity-only crossfade ≤ 150ms

## Microinteractions stance
- Silent success (no toast) for visible mutations; toast only for failures/invisible effects
- Hover delay: 800ms; Focus delay: 0ms (instant focus ring)
- 8-state discipline on every interactive: default · hover · focus-visible · active · disabled · loading · error · success

## CTA voice
- Primary: fill `--color-accent`, rounded-xl (12px), px-6 py-2.5, font-semibold, no border
- Secondary: outline `--color-rule` border, bg `--color-paper`, text `--color-ink`, rounded-xl, px-6 py-2.5
- Danger: bg `--color-error/10`, text `--color-error`, border `--color-error/30`, rounded-xl

## Per-page allowances
- Marketing pages MAY use Tier-A CSS art enrichment (clipped-edge, geometric accent)
- App pages MUST NOT use enrichment — function carries the page
- Content pages: typography only

## What pages MUST share
- Wordmark "PasPapan" / HRConnect logotype
- Accent colour `--color-accent` placement ≤ 5% per viewport
- Display + body font pairing (Rubik + Inter)
- CTA voice (button shape, radius, padding rhythm)
- Section heading rhythm: eyebrow (ess-eyebrow) + display heading

## What pages MAY differ on
- Macrostructure within family (Marquee Hero vs Long Document for marketing)
- Hero archetype (within family allowance)
- Enrichment — only marketing, only Tier-A/Tier-B

## Exports

### tokens.css (CSS custom properties)
:root {
  --color-paper:      oklch(100% 0 0);
  --color-paper-2:    oklch(98.5% 0.003 240);
  --color-paper-3:    oklch(94% 0.006 240);
  --color-ink:        oklch(20% 0.02 255);
  --color-ink-2:      oklch(45% 0.02 255);
  --color-rule:       oklch(88% 0.01 240);
  --color-accent:     oklch(58% 0.22 260);
  --color-accent-ink: oklch(100% 0 0);
  --color-focus:      oklch(58% 0.22 260);
  --color-success:    oklch(60% 0.18 150);
  --color-warning:    oklch(75% 0.18 85);
  --color-error:      oklch(58% 0.22 25);

  --color-module-hr:         oklch(58% 0.22 260);
  --color-module-attendance: oklch(70% 0.14 200);
  --color-module-leave:      oklch(60% 0.20 295);
  --color-module-payroll:    oklch(65% 0.17 160);
  --color-module-documents:  oklch(70% 0.17 75);
  --color-module-reports:    oklch(60% 0.18 230);
  --color-module-kb:         oklch(60% 0.22 330);
  --color-module-finance:    oklch(65% 0.20 20);

  --font-display: "Rubik", "Inter", sans-serif;
  --font-body:    "Inter", system-ui, sans-serif;
  --font-mono:    "Monaco", "Consolas", monospace;

  --space-3xs: 0.25rem;  --space-2xs: 0.5rem;  --space-xs: 0.75rem;
  --space-sm:  1rem;     --space-md:  1.5rem;  --space-lg: 2rem;
  --space-xl:  3rem;     --space-2xl: 4.5rem;  --space-3xl: 7rem;

  --text-xs: 0.75rem;   --text-sm: 0.875rem; --text-md: 1rem;
  --text-lg: 1.125rem;  --text-xl: 1.25rem;  --text-2xl: 1.5rem;
  --text-display: clamp(2.25rem, 4vw + 1rem, 3.5rem);

  --ease-out:     cubic-bezier(0.16, 1, 0.3, 1);
  --ease-in-out:  cubic-bezier(0.4, 0, 0.2, 1);
  --dur-short:    220ms;
  --radius-card:  16px;
  --radius-pill:  9999px;
  --radius-input: 12px;
}

### Tailwind v4 @theme (for app.css)
@theme {
  --color-paper:      oklch(100% 0 0);
  --color-paper-2:    oklch(98.5% 0.003 240);
  --color-paper-3:    oklch(94% 0.006 240);
  --color-ink:        oklch(20% 0.02 255);
  --color-ink-2:      oklch(45% 0.02 255);
  --color-rule:       oklch(88% 0.01 240);
  --color-accent:     oklch(58% 0.22 260);
  --color-accent-ink: oklch(100% 0 0);
  --color-focus:      oklch(58% 0.22 260);
  --color-success:    oklch(60% 0.18 150);
  --color-warning:    oklch(75% 0.18 85);
  --color-error:      oklch(58% 0.22 25);

  --color-module-hr:         oklch(58% 0.22 260);
  --color-module-attendance: oklch(70% 0.14 200);
  --color-module-leave:      oklch(60% 0.20 295);
  --color-module-payroll:    oklch(65% 0.17 160);
  --color-module-documents:  oklch(70% 0.17 75);
  --color-module-reports:    oklch(60% 0.18 230);
  --color-module-kb:         oklch(60% 0.22 330);
  --color-module-finance:    oklch(65% 0.20 20);

  --font-display: "Rubik", "Inter", sans-serif;
  --font-body:    "Inter", system-ui, sans-serif;
  --font-mono:    "Monaco", "Consolas", monospace;

  --spacing-3xs: 0.25rem;  --spacing-2xs: 0.5rem;  --spacing-xs: 0.75rem;
  --spacing-sm:  1rem;     --spacing-md:  1.5rem;  --spacing-lg: 2rem;
  --spacing-xl:  3rem;     --spacing-2xl: 4.5rem;  --spacing-3xl: 7rem;

  --text-xs: 0.75rem;   --text-sm: 0.875rem; --text-md: 1rem;
  --text-lg: 1.125rem;  --text-xl: 1.25rem;  --text-2xl: 1.5rem;
  --text-display: clamp(2.25rem, 4vw + 1rem, 3.5rem);

  --ease-out:     cubic-bezier(0.16, 1, 0.3, 1);
  --ease-in-out:  cubic-bezier(0.4, 0, 0.2, 1);
  --dur-short:    220ms;
  --radius-card:  16px;
  --radius-pill:  9999px;
  --radius-input: 12px;
}