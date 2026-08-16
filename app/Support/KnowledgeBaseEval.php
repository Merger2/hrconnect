<?php

declare(strict_types=1);

namespace App\Support;

/**
 * KnowledgeBaseEval — metrik evaluasi RAG Knowledge Base (kerangka eval Q5).
 *
 * Metrik (PRD §6 / AGENTS.md hard gate):
 * 1. Coverage — persentase keyword/frasa kunci `expected_answer` yang muncul
 *    dalam jawaban (case-insensitive, dinormalisasi: lowercase + tanpa tanda baca).
 * 2. Citation — minimal 1 `expected_sources` (judul entry KB) yang cocok dengan
 *    sumber yang disertakan pipeline (jawaban/sumber wajib punya citation).
 * 3. Refusal — deteksi penolakan untuk kasus negatif (di luar corpus): sistem
 *    TIDAK boleh menjawab palsu.
 *
 * Murni helper eval — tidak dipakai di pipeline produksi.
 */
final class KnowledgeBaseEval
{
    /**
     * Normalisasi teks untuk pencocokan case-insensitive: lowercase, strip tanda
     * baca/whitespace berlebih, normalisasi angka desimal (koma -> titik).
     */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text);

        // Normalisasi angka desimal "Rp60,5" -> "rp60.5"
        $text = preg_replace('/(\d),(\d)/u', '$1.$2', $text) ?? $text;

        // Ganti tanda baca dengan spasi, rapikan whitespace
        $text = preg_replace('/[^\p{L}\p{N}\s%.+-]/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /**
     * Coverage: proporsi keyword yang muncul di output pipeline (>= 1.0 = semua).
     *
     * Output diukur dari jawaban PENUH pipeline: teks jawaban + konten sumber
     * yang dikutip (title + snippet). Sejak fallback tidak lagi menyertakan
     * dump konten mentah di teks jawaban (2026-08-16 — jawaban profesional,
     * konten ditampilkan via citation/sources), coverage harus mencakup materi
     * yang benar-benar diretri eval — konsisten dengan gate "citation pada
     * setiap jawaban".
     *
     * @param  array<int, string>  $keywords
     * @param  array<int, array{id?: int|string, title?: string, snippet?: string}>  $sources
     */
    public static function coverage(string $answer, array $keywords, array $sources = []): float
    {
        if ($keywords === []) {
            return 1.0;
        }

        $material = $answer;

        foreach ($sources as $source) {
            $material .= ' '.($source['title'] ?? '').' '.($source['snippet'] ?? '');
        }

        $normalizedMaterial = self::normalize($material);
        $matched = 0;

        foreach ($keywords as $keyword) {
            $normalizedKeyword = self::normalize($keyword);

            if ($normalizedKeyword !== '' && str_contains($normalizedMaterial, $normalizedKeyword)) {
                $matched++;
            }
        }

        return $matched / count($keywords);
    }

    /**
     * Citation: apakah minimal satu sumber yang diharapkan muncul di daftar
     * sumber pipeline (dicocokkan per judul entry, case-insensitive).
     *
     * @param  array<int, array{id?: int|string, title?: string, snippet?: string}>  $sources
     * @param  array<int, string>  $expectedSources
     */
    public static function citation(array $sources, array $expectedSources): bool
    {
        if ($expectedSources === []) {
            return true;
        }

        $titles = array_values(array_filter(array_map(
            static fn (array $source): string => self::normalize((string) ($source['title'] ?? '')),
            $sources,
        )));

        if ($titles === []) {
            return false;
        }

        foreach ($expectedSources as $expectedTitle) {
            if (in_array(self::normalize($expectedTitle), $titles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Refusal: deteksi penolakan (tidak menjawab palsu) untuk kasus negatif.
     * Jawaban kosong atau mengandung frasa penolakan standar pipeline.
     */
    public static function refusal(string $answer): bool
    {
        $normalized = self::normalize($answer);

        if (trim($normalized) === '') {
            return true;
        }

        foreach (self::refusalPhrases() as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Frasa penolakan yang dihasilkan pipeline nyata:
     * - fallback pg_trgm tanpa match: "tidak ada informasi yang cocok"
     * - agent instructions: "informasi tersebut belum tersedia"
     * - error handler chat: "layanan AI sedang tidak tersedia"
     */
    public static function refusalPhrases(): array
    {
        return [
            'tidak ada informasi',
            'belum tersedia',
            'tidak ditemukan',
            'tidak tersedia',
            'tidak dapat menjawab',
            'tidak ada jawaban',
            'maaf',
        ];
    }

    /**
     * Evaluasi satu kasus: coverage >= threshold DAN citation terpenuhi.
     *
     * @param  array<int, string>  $keywords
     * @param  array<int, string>  $expectedSources
     * @param  array<int, array{id?: int|string, title?: string, snippet?: string}>  $sources
     * @return array{coverage: float, citation: bool, passed: bool}
     */
    public static function evaluateCase(
        string $answer,
        array $keywords,
        array $sources,
        array $expectedSources,
        float $coverageThreshold = 0.8,
    ): array {
        $coverage = self::coverage($answer, $keywords, $sources);
        $citation = self::citation($sources, $expectedSources);

        return [
            'coverage' => $coverage,
            'citation' => $citation,
            'passed' => $coverage >= $coverageThreshold && $citation,
        ];
    }
}
