<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

class AuditUi extends Command
{
    protected $signature = 'hrconnect:audit-ui
        {--path=views : Sub-path under resources/ to scan}
        {--json : Output raw JSON instead of table}';

    protected $description = 'Audit UI/UX issues across all Blade views (hardcoded colors, missing placeholders, etc.)';

    /**
     * Tailwind color families that should NOT appear as raw utilities.
     * We allow only our MD3 design tokens (bg-primary, text-on-surface-variant, ...).
     */
    private const RAW_COLOR_PATTERN = '/(?<![\w-])(?:bg|text|border|ring|from|to|via|divide|outline|decoration|fill|stroke)-(?:blue|gray|grey|red|green|emerald|yellow|amber|orange|purple|pink|fuchsia|rose|indigo|violet|cyan|teal|lime|sky|slate|zinc|neutral|stone)-(?:\d{2,3})\b/';

    private const RAW_HEX_PATTERN = '/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})\b/';

    public function handle(): int
    {
<<<<<<< HEAD
        $base = resource_path($this->option('path'));
        if (! is_dir($base)) {
            $this->error("Path not found: {$base}");

            return self::FAILURE;
        }

        $files = File::allFiles($base);
        $report = [];

        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            if ($file->getExtension() !== 'blade.php' && $file->getExtension() !== 'php') {
                continue;
            }

            $relative = $file->getRelativePathname();
            // Skip vendor + compiled
            if (str_starts_with($relative, 'vendor') || str_contains($relative, '\\')) {
                continue;
            }

            $content = $file->getContents();
            $issues = $this->scanFile($content, $relative);

            if (! empty($issues)) {
                $report[$relative] = $issues;
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        if (empty($report)) {
            $this->info('✅ No UI/UX issues found in '.$this->option('path'));

            return self::SUCCESS;
        }

        $this->warn('Found '.count($report).' file(s) with potential UI/UX issues:');
        $this->newLine();

        $rows = [];
        $total = 0;
        foreach ($report as $file => $issues) {
            foreach ($issues as $issue) {
                $rows[] = [$file, $issue['type'], $issue['severity'], $issue['detail']];
                $total++;
            }
        }

        $this->table(['File', 'Issue', 'Severity', 'Detail'], $rows);
        $this->newLine();
        $this->info("Total: {$total} issue(s) across ".count($report).' file(s).');
        $this->comment('Run with --json for machine-readable output.');

        return self::SUCCESS;
=======
        try {
            $base = resource_path($this->option('path'));
            if (! is_dir($base)) {
                $this->error("Path not found: {$base}");

                return self::FAILURE;
            }

            $files = File::allFiles($base);
            $report = [];

            foreach ($files as $file) {
                /** @var SplFileInfo $file */
                if ($file->getExtension() !== 'blade.php' && $file->getExtension() !== 'php') {
                    continue;
                }

                $relative = $file->getRelativePathname();
                // Skip vendor + compiled
                if (str_starts_with($relative, 'vendor') || str_contains($relative, '\\')) {
                    continue;
                }

                $content = $file->getContents();
                $issues = $this->scanFile($content, $relative);

                if (! empty($issues)) {
                    $report[$relative] = $issues;
                }
            }

            if ($this->option('json')) {
                $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                return self::SUCCESS;
            }

            if (empty($report)) {
                $this->info('✅ No UI/UX issues found in '.$this->option('path'));

                return self::SUCCESS;
            }

            $this->warn('Found '.count($report).' file(s) with potential UI/UX issues:');
            $this->newLine();

            $rows = [];
            $total = 0;
            foreach ($report as $file => $issues) {
                foreach ($issues as $issue) {
                    $rows[] = [$file, $issue['type'], $issue['severity'], $issue['detail']];
                    $total++;
                }
            }

            $this->table(['File', 'Issue', 'Severity', 'Detail'], $rows);
            $this->newLine();
            $this->info("Total: {$total} issue(s) across ".count($report).' file(s).');
            $this->comment('Run with --json for machine-readable output.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Audit UI gagal: {$e->getMessage()}");
            report($e);

            return self::FAILURE;
        }
>>>>>>> main
    }

    /**
     * @return array<int, array{type: string, severity: string, detail: string, line?: int}>
     */
    private function scanFile(string $content, string $file): array
    {
        $issues = [];
        $lines = explode("\n", $content);

        // 1. Raw Tailwind color utilities
        foreach ($lines as $i => $line) {
            if (preg_match_all(self::RAW_COLOR_PATTERN, $line, $m)) {
                $issues[] = [
                    'type' => 'raw_color',
                    'severity' => 'high',
                    'detail' => 'Line '.($i + 1).': '.implode(', ', array_unique($m[0])),
                    'line' => $i + 1,
                ];
            }
        }

        // 2. Hardcoded hex colors (excluding in <style> blocks is hard; flag all)
        if (preg_match_all(self::RAW_HEX_PATTERN, $content, $m)) {
            $issues[] = [
                'type' => 'raw_hex',
                'severity' => 'medium',
                'detail' => 'Hex colors found: '.implode(', ', array_unique($m[0])),
            ];
        }

        // 3. x-forms.input / x-forms.textarea without placeholder
        if (preg_match_all('/<x-forms\.(input|textarea|select|datepicker)([^>]*)>/', $content, $m, PREG_SET_ORDER)) {
            foreach ($m as $mm) {
                $attrs = $mm[2];
                if (! preg_match('/\bplaceholder\s*=/', $attrs) && ! preg_match('/\b:placeholder\s*=/', $attrs)) {
                    $issues[] = [
                        'type' => 'missing_placeholder',
                        'severity' => 'medium',
                        'detail' => "Form field '{$mm[1]}' without placeholder attribute",
                    ];
                }
            }
        }

        // 4. Inputs (native) without placeholder
        if (preg_match_all('/<input\b[^>]*>/', $content, $m)) {
            foreach ($m as $mm) {
                if (! preg_match('/\bplaceholder\s*=/', $mm[0]) && ! preg_match('/\btype\s*=\s*["\'](hidden|submit|checkbox|radio|file)["\']/', $mm[0])) {
                    $issues[] = [
                        'type' => 'missing_placeholder',
                        'severity' => 'low',
                        'detail' => 'Native <input> without placeholder',
                    ];
                }
            }
        }

        // 5. wire:model.live select without wire:loading (cascading dropdown anti-pattern)
        if (preg_match('/wire:model\.live/', $content) && ! preg_match('/wire:loading/', $content)) {
            $issues[] = [
                'type' => 'no_loading_state',
                'severity' => 'medium',
                'detail' => 'Has wire:model.live but no wire:loading indicator (cascading dropdowns need feedback)',
            ];
        }

        // 6. Missing transition on interactive hover (coarse)
        if (preg_match('/hover:bg-/', $content) && ! preg_match('/transition/', $content)) {
            $issues[] = [
                'type' => 'no_transition',
                'severity' => 'low',
                'detail' => 'Hover state without transition utility',
            ];
        }

        // 7. Missing aria-label on icon-only buttons/links
        if (preg_match_all('/<(button|a)\b[^>]*material-symbols-outlined[^>]*>/', $content, $m)) {
            foreach ($m[0] as $tag) {
                if (! preg_match('/\baria-label\s*=/', $tag) && ! preg_match('/>[^<]*</', $tag)) {
                    $issues[] = [
                        'type' => 'a11y_icon_only',
                        'severity' => 'low',
                        'detail' => 'Icon-only control without aria-label',
                    ];
                }
            }
        }

        // 8. duplicate face-registration references (redundancy check)
        if (substr_count($content, 'attendance.face-registration') > 1) {
            $issues[] = [
                'type' => 'face_redundancy',
                'severity' => 'info',
                'detail' => 'Multiple references to attendance.face-registration in one file',
            ];
        }

        return $issues;
    }
}
