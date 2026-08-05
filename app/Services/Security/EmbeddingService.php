<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\KnowledgeBaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\KnowledgeBase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;
use Smalot\PdfParser\Parser;

class EmbeddingService
{
    protected const CHUNK_MAX_CHARS = 1000;

    protected const CHUNK_OVERLAP = 200;

    protected const EMBEDDING_DIMENSION = 768;

    /**
     * Stopword umum Bahasa Indonesia — kata fungsi bernoise tinggi yang tidak
     * dijadikan token pencarian keyword. Token konten seperti "perusahaan",
     * "karyawan", "keterlambatan" TIDAK masuk daftar ini.
     *
     * @var array<int, string>
     */
    protected const KEYWORD_STOPWORDS = [
        'ada', 'agar', 'akan', 'anda', 'apa', 'apakah', 'apabila', 'atau',
        'bagaimana', 'bisa', 'dalam', 'dan', 'dapat', 'dari', 'di', 'dimana',
        'dengan', 'ini', 'itu', 'juga', 'kapan', 'kami', 'ke', 'mengapa',
        'mohon', 'pada', 'saja', 'saya', 'siapa', 'sudah', 'supaya', 'tidak',
        'untuk', 'yang',
    ];

    public function __construct() {}

    public function chunkText(string $text, int $chunkChars = self::CHUNK_MAX_CHARS, int $overlapChars = self::CHUNK_OVERLAP): array
    {
        if (trim($text) === '' || mb_strlen($text) < 50) {
            return [];
        }

        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $end = min($start + $chunkChars, $length);
            $chunk = mb_substr($text, $start, $end - $start);
            $chunks[] = trim($chunk);
            $start += $chunkChars - $overlapChars;
        }

        return array_filter($chunks, fn ($c) => mb_strlen($c) >= 50);
    }

    public function formatVector(array $vector): string
    {
        return '['.implode(',', $vector).']';
    }

    public function embed(string $text): array
    {
        if (trim($text) === '' || mb_strlen($text) > 30000) {
            throw new BusinessRuleException('Teks untuk embedding harus 1-30000 karakter.');
        }

        // Test mode: return deterministic fake embedding
        if (app()->runningUnitTests()) {
            return Embeddings::fakeEmbedding(self::EMBEDDING_DIMENSION);
        }

        // Production mode: Use configured provider and model from config/ai.php
        // Driver gemini uses 'gemini-embedding-001' by default if specified or falls back
        $response = Embeddings::for([$text])
            ->dimensions(self::EMBEDDING_DIMENSION)
            ->generate(Lab::Gemini, config('ai.providers.gemini.embedding_model', 'gemini-embedding-001'));

        $vector = $response->first();

        if (count($vector) !== self::EMBEDDING_DIMENSION) {
            throw new BusinessRuleException('Embedding provider tidak mengembalikan '.self::EMBEDDING_DIMENSION.' dimensi.');
        }

        return $vector;
    }

    public function processKnowledgeBase(KnowledgeBase $kb): void
    {
        if (empty($kb->content)) {
            $kb->update(['status' => KnowledgeBaseStatus::ERROR]);

            return;
        }

        try {
            $embedding = $this->embed($kb->content);
            $vectorString = $this->formatVector($embedding);

            DB::statement(
                'UPDATE knowledge_bases SET embedding = ?::vector, status = ?, updated_at = ? WHERE id = ?',
                [$vectorString, KnowledgeBaseStatus::READY->value, now(), $kb->id]
            );
        } catch (\Throwable $e) {
            $kb->update(['status' => KnowledgeBaseStatus::ERROR]);
            throw new BusinessRuleException('Gagal generate embedding: '.$e->getMessage());
        }
    }

    public function searchSimilar(array $queryVector, int $topK = 5): Collection
    {
        if (count($queryVector) !== self::EMBEDDING_DIMENSION) {
            // Re-generate or fallback if dimension mismatches
            $queryVector = Embeddings::fakeEmbedding(self::EMBEDDING_DIMENSION);
        }

        $vectorString = $this->formatVector($queryVector);

        return KnowledgeBase::query()
            ->where('status', KnowledgeBaseStatus::READY)
            ->whereNotNull('embedding')
            ->selectRaw('*, 1 - (embedding <=> ?::vector) as similarity', [$vectorString])
            ->orderByDesc('similarity')
            ->limit($topK)
            ->get();
    }

    /**
     * Token-based keyword search (fallback pg_trgm → ILIKE).
     *
     * Pertanyaan natural panjang di-tokenize (lowercase, split whitespace,
     * buang token < 3 karakter + stopword umum), lalu chunk dicari dengan
     * OR match per token. Hasil di-scoring di PHP berdasarkan jumlah token
     * yang muncul di content (desc), diambil topK.
     *
     * Perbaikan bug 2026-08-05: sebelumnya `$keyword` utuh dipakai sebagai
     * substring persis (ILIKE %kalimat%) → pertanyaan kalimat penuh ("bagaimana
     * keterlambatan kerja di perusahaan ini") tidak pernah match content
     * → fallback gagal menemukan chunk padahal corpus punya topiknya.
     */
    public function searchByKeyword(string $keyword, int $topK = 5): Collection
    {
        $tokens = $this->keywordTokens($keyword);

        if ($tokens === []) {
            return new Collection;
        }

        $candidates = KnowledgeBase::query()
            ->where('status', KnowledgeBaseStatus::READY)
            ->where(function ($query) use ($tokens): void {
                $query->where('content', 'ILIKE', '%'.$tokens[0].'%');

                foreach (array_slice($tokens, 1) as $token) {
                    $query->orWhere('content', 'ILIKE', '%'.$token.'%');
                }
            })
            ->limit(20)
            ->get();

        if ($candidates->isEmpty()) {
            return $candidates;
        }

        // Eloquent Collection::map() otomatis turun ke base Collection saat item
        // bukan Model (array skor) — bungkus ulang agar return type terjaga.
        // Skor = token match di content (word boundary) + 2× token match di TITLE
        // (judul dokumen = sinyal relevansi kuat; tanpa ini chunk bertopik spesifik
        // kalah oleh token generik, mis. "keterlambatan" vs "kerja/perusahaan").
        // Tie-break: (score desc, id asc) — uasort/sortByDesc tidak stabil di PHP.
        $ranked = $candidates
            ->map(fn (KnowledgeBase $kb) => [
                'kb' => $kb,
                'score' => $this->countTokenMatches($tokens, mb_strtolower($kb->content))
                    + (2 * $this->countTokenMatches($tokens, mb_strtolower($kb->title))),
            ])
            ->filter(fn (array $row) => $row['score'] > 0)
            ->sortBy(fn (array $row) => [-$row['score'], $row['kb']->id])
            ->take($topK)
            ->map(fn (array $row) => $row['kb'])
            ->values();

        return new Collection($ranked->all());
    }

    /**
     * Tokenize query pencarian: lowercase, split whitespace, strip tanda baca,
     * buang token < 3 karakter dan stopword umum Bahasa Indonesia (kata fungsi
     * bernoise tinggi). Token panjang seperti "perusahaan" TIDAK dibuang —
     * hanya kata fungsi pendek yang hilang.
     *
     * @return array<int, string>
     */
    protected function keywordTokens(string $keyword): array
    {
        $tokens = preg_split('/\s+/u', mb_strtolower(trim($keyword))) ?: [];

        return array_values(array_filter(array_map(
            fn (string $token) => $this->cleanToken($token),
            $tokens,
        )));
    }

    /**
     * Bersihkan token: strip tanda baca, buang yang < 3 karakter atau stopword.
     *
     * @return string|false token bersih, atau false jika token tidak layak
     */
    protected function cleanToken(string $token): string|false
    {
        $clean = preg_replace('/[^\p{L}\p{N}]+/u', '', $token) ?? '';

        if (mb_strlen($clean) < 3 || in_array($clean, self::KEYWORD_STOPWORDS, true)) {
            return false;
        }

        return $clean;
    }

    /**
     * Hitung jumlah token yang muncul sebagai kata utuh di content
     * (case-insensitive, word boundary). Substring insidental seperti "kerja"
     * di dalam "Ketenagakerjaan" TIDAK dihitung — scoring mencerminkan relevansi
     * kata kunci, bukan kemiripan ejaan.
     *
     * @param  array<int, string>  $tokens
     */
    protected function countTokenMatches(array $tokens, string $content): int
    {
        $matches = 0;

        foreach ($tokens as $token) {
            if (preg_match('/\b'.preg_quote($token, '/').'\b/u', $content)) {
                $matches++;
            }
        }

        return $matches;
    }

    public function extractTextFromPdf(string $filePath): string
    {
        if (! file_exists($filePath)) {
            throw new BusinessRuleException('File PDF tidak dapat dibaca');
        }

        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($filePath);

            return $pdf->getText();
        } catch (\Throwable $e) {
            throw new BusinessRuleException('Gagal mengekstrak teks dari PDF: '.$e->getMessage());
        }
    }
}
