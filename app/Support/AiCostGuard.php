<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AiCostGuard — daily token budget guard untuk pipeline AI (KB RAG).
 *
 * Menutup bagian "cost limit" dari hard gate AGENTS.md
 * ("AI KB hard gate: embedding 768D nyata, timeout/retry/cost limit,
 * eval >= 20 Q&A, kualitas >= 90%"). Timeout + retry sudah ada di
 * config/services.php (gemini); guard ini menegakkan batas token per hari.
 *
 * Kuota dihitung global (bukan per-user) via cache database dengan key
 * ber-awalan tanggal — otomatis reset tiap hari. Budget 0 = nonaktif.
 *
 * Tidak ada degradasi senyap: saat kuota habis, pemanggil WAJIB fallback ke
 * jalur gratis (pg_trgm keyword search) dan menandai `fallback: true` plus
 * Log::warning — konsisten dengan rule no-silent-degradation.
 */
class AiCostGuard
{
    /**
     * Estimasi output token maksimum per generasi — selaras dengan
     * #[MaxTokens(1024)] di HrKnowledgeBaseAgent.
     */
    public const ESTIMATED_MAX_OUTPUT_TOKENS = 1024;

    public function budget(): int
    {
        return max(0, (int) config('services.gemini.daily_token_budget', 1_000_000));
    }

    /**
     * Budget <= 0 berarti limit nonaktif (unlimited).
     */
    public function isEnabled(): bool
    {
        return $this->budget() > 0;
    }

    public function spent(): int
    {
        return (int) Cache::get($this->cacheKey(), 0);
    }

    public function remaining(): int
    {
        if (! $this->isEnabled()) {
            return PHP_INT_MAX;
        }

        return max(0, $this->budget() - $this->spent());
    }

    public function canSpend(int $tokens): bool
    {
        if ($tokens <= 0 || ! $this->isEnabled()) {
            return true;
        }

        return $this->spent() + $tokens <= $this->budget();
    }

    /**
     * Catat pemakaian token (jumlah atomik di cache database).
     */
    public function record(int $tokens): void
    {
        if ($tokens <= 0 || ! $this->isEnabled()) {
            return;
        }

        $key = $this->cacheKey();

        Cache::add($key, 0, now()->addDays(2));
        Cache::increment($key, $tokens);
    }

    /**
     * Hapus akumulasi hari ini (dipakai test / admin override).
     */
    public function reset(): void
    {
        Cache::forget($this->cacheKey());
    }

    /**
     * Estimasi token: rata-rata ~4 karakter per token (Bahasa Indonesia
     * mendekati 3.5-4 karakter/token). Dipakai saat usage metadata nyata
     * tidak tersedia (mis. embedding) atau sebagai guard pre-flight.
     */
    public function estimateTokens(string $text): int
    {
        $length = mb_strlen(trim($text));

        return $length > 0 ? (int) ceil($length / 4) : 0;
    }

    /**
     * Pesan standar saat kuota habis — konsisten di chat/chatStream/embed.
     */
    public function exhaustedMessage(): string
    {
        Log::warning('AI daily token budget exhausted', [
            'spent' => $this->spent(),
            'budget' => $this->budget(),
        ]);

        return 'Kuota penggunaan AI harian untuk hari ini telah tercapai.';
    }

    protected function cacheKey(): string
    {
        return 'ai:token_usage:'.now()->format('Y-m-d');
    }
}
