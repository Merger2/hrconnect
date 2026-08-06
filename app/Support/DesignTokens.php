<?php

namespace App\Support;

/**
 * Design tokens — PHP, untuk renderer NON-CSS (Dompdf PDF, email client).
 *
 * Kenapa ada: Dompdf & client email (Outlook/Gmail) TIDAK me-resolve
 * `var(--color-*)` / `color-mix()`. Template pdf/*.blade.php dan
 * emails/*.blade.php memanggil design_token('...') → Blade me-render HEX
 * literal saat output, jadi kompatibel penuh sekaligus bebas hardcode.
 *
 * Arsitektur dua-lapis (design.md — TOKEN-ONLY RULE):
 *   - Web UI        → @theme di resources/css/app.css (CSS custom property).
 *   - PDF / email   → kelas ini (PHP).
 *   - CORE (primary/brand/module/success/dst) ADA DI KEDUANYA dan harus
 *     SINKRON — dicek otomatis oleh scripts/check-token-sync.php.
 *   - Docs family (brand-green-*, muted-green-*, status-*, warning-*) hanya
 *     di sini — web UI tidak memakainya, tidak perlu masuk @theme.
 */
final class DesignTokens
{
    /**
     * Core tokens — MIRROR @theme resources/css/app.css.
     * Jaga tetap sinkron! Verifikasi: `php scripts/check-token-sync.php`.
     */
    private const CORE = [
        // Neutral dark primary (slate)
        'primary' => '#1e293b',
        'primary-50' => '#f8fafc',
        'primary-100' => '#f1f5f9',
        'primary-200' => '#e2e8f0',
        'primary-300' => '#cbd5e1',
        'primary-400' => '#94a3b8',
        'primary-500' => '#64748b',
        'primary-600' => '#475569',
        'primary-700' => '#334155',
        'primary-800' => '#1e293b',
        'primary-900' => '#0f172a',
        'primary-950' => '#020617',
        'primary-deep' => '#0f172a',
        'primary-soft' => '#f1f5f9',

        // Brand blue — khusus marketing/auth
        'brand-50' => '#eef4ff',
        'brand-100' => '#d1e0ff',
        'brand-200' => '#a6c8ff',
        'brand-300' => '#70a8ff',
        'brand-400' => '#3b82f6',
        'brand-500' => '#024ad8',
        'brand-600' => '#0240b8',
        'brand-700' => '#0136a0',
        'brand-800' => '#012c88',
        'brand-900' => '#002070',
        'brand-950' => '#001458',

        // Semantic + netral
        'surface' => '#ffffff',
        'surface-variant' => '#f5f7fa',
        'ink' => '#1a1a1a',
        'muted' => '#5f6369',
        'outline' => '#c9cdd4',
        'error' => '#c0392b',
        'success' => '#059669',
        'focus' => '#334155',
        'on-primary' => '#ffffff',
        'on-error' => '#ffffff',

        // Modular Hue System
        'module-hr' => '#3b82f6',
        'module-attendance' => '#06b6d4',
        'module-leave' => '#8b5cf6',
        'module-payroll' => '#10b981',
        'module-documents' => '#f59e0b',
        'module-reports' => '#0284c7',
        'module-kb' => '#d946ef',
        'module-finance' => '#f43f5e',
    ];

    /**
     * Docs family — brand green PT DCM + status badge + warning email.
     * Hanya dipakai PDF/email (DesignTokens); TIDAK masuk @theme.
     */
    private const DOCS = [
        // Brand deep (navy-teal) — header/akor PDF
        'brand-deep' => '#083344',

        // Brand green scale (dokumen & email)
        'brand-green-50' => '#f5faf4',
        'brand-green-100' => '#e2efe0',
        'brand-green-200' => '#d5ead1',
        'brand-green-300' => '#badcb3',
        'brand-green-500' => '#6ab45b',
        'brand-green-600' => '#57944a',
        'brand-green-700' => '#44733a',
        'brand-green-800' => '#31542a',
        'brand-green-900' => '#166534',
        'brand-green-950' => '#163020',

        // Muted green — teks email
        'muted-green-500' => '#6b7f71',
        'muted-green-600' => '#5d7766',
        'muted-green-700' => '#466351',
        'muted-green-800' => '#355340',

        // Status badge (appraisal PDF)
        'status-good' => '#166534',
        'status-good-soft' => '#dcfce7',
        'status-watch' => '#92400e',
        'status-watch-soft' => '#fef3c7',
        'status-risk' => '#991b1b',
        'status-risk-soft' => '#fee2e2',
        'status-red' => '#b91c1c',

        // Warning (email-note)
        'warning-deep' => '#744210',
        'warning' => '#b7791f',
        'warning-soft' => '#fff9eb',
    ];

    public static function all(): array
    {
        return self::CORE + self::DOCS;
    }

    public static function get(string $key, ?string $fallback = null): string
    {
        return self::all()[$key] ?? $fallback ?? self::CORE['primary-700'];
    }

    /**
     * Token hex → rgba() dengan alpha. Dipakai email client yang
     * mendukung rgba (alpha varian dari token, bukan hex hardcode).
     */
    public static function rgba(string $key, float $alpha): string
    {
        $hex = ltrim(self::get($key), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $alphaStr = rtrim(rtrim(sprintf('%.2f', $alpha), '0'), '.');

        return sprintf(
            'rgba(%d, %d, %d, %s)',
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
            $alphaStr,
        );
    }
}
