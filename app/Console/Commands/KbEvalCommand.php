<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\KnowledgeBase;
use App\Services\KnowledgeBase\KnowledgeBaseService;
use App\Support\KnowledgeBaseEval;
use Illuminate\Console\Command;

/**
 * Eval dataset AI Knowledge Base (Q5 — AGENTS.md hard gate / PRD §6).
 *
 * Menjalankan dataset Q&A dari tests/Fixtures/kb-eval-dataset.json melawan
 * pipeline RAG NYATA (KnowledgeBaseService::chat):
 * - Online: embedding Gemini + generasi Gemini (butuh GEMINI_API_KEY/GOOGLE_AI_API_KEY).
 * - Tanpa key: pipeline otomatis fallback ke pg_trgm (searchByKeyword) — mode offline.
 *
 * Skor: kualitas = kasus positif lulus (coverage >= 80% + citation) / total positif.
 * Exit code 0 = kualitas >= 90% DAN semua kasus negatif ditolak; selain itu 1.
 *
 * Eval bersifat manual (gate) — TIDAK terdaftar di scheduler.
 */
class KbEvalCommand extends Command
{
    protected $signature = 'kb:eval
        {--dataset=tests/Fixtures/kb-eval-dataset.json : Path dataset evaluasi relatif ke base path}
        {--coverage-threshold=0.8 : Ambang coverage keyword per kasus}
        {--quality-threshold=0.9 : Ambang kualitas agregat (lulus/total positif)}';

    protected $description = 'Jalankan eval dataset Q&A Knowledge Base (gate PRD §6: >= 90% relevan + citation).';

    public function handle(KnowledgeBaseService $kbService): int
    {
        if (KnowledgeBase::query()->count() === 0) {
            $this->components->error('Corpus Knowledge Base kosong. Jalankan dulu: php artisan db:seed --class=KnowledgeBaseSeeder');

            return self::FAILURE;
        }

        $datasetPath = base_path($this->option('dataset'));

        if (! is_file($datasetPath)) {
            $this->components->error("Dataset tidak ditemukan: {$datasetPath}");

            return self::FAILURE;
        }

        $dataset = json_decode(file_get_contents($datasetPath), true, flags: JSON_THROW_ON_ERROR);
        $cases = $dataset['cases'];

        $coverageThreshold = (float) $this->option('coverage-threshold');
        $qualityThreshold = (float) $this->option('quality-threshold');

        $apiKey = config('ai.providers.gemini.key') ?: env('GEMINI_API_KEY') ?: env('GOOGLE_AI_API_KEY');
        $mode = $apiKey ? 'online (Gemini)' : 'offline (fallback pg_trgm — set GEMINI_API_KEY/GOOGLE_AI_API_KEY untuk retrieval semantic nyata)';

        $this->components->info(sprintf('Eval KB dataset: %d kasus | mode: %s', count($cases), $mode));

        $rows = [];
        $positiveTotal = 0;
        $positivePassed = 0;
        $negativeTotal = 0;
        $negativePassed = 0;

        foreach ($cases as $case) {
            $response = $kbService->chat((string) $case['question']);

            $answer = (string) ($response['answer'] ?? '');
            $sources = $response['sources'] ?? [];
            $model = (string) ($response['model'] ?? '-');

            if ($case['negative']) {
                $negativeTotal++;
                $refused = KnowledgeBaseEval::refusal($answer);

                if ($refused) {
                    $negativePassed++;
                }

                $rows[] = [
                    $case['id'],
                    $case['category'],
                    'NEGATIF',
                    $refused ? 'DITOLAK ✓' : 'DIJAWAB ✗',
                    '-',
                    $model,
                ];

                continue;
            }

            $positiveTotal++;
            $metrics = KnowledgeBaseEval::evaluateCase(
                $answer,
                $case['expected_answer'],
                $sources,
                $case['expected_sources'],
                $coverageThreshold,
            );

            if ($metrics['passed']) {
                $positivePassed++;
            }

            $rows[] = [
                $case['id'],
                $case['category'],
                substr($case['question'], 0, 60),
                $metrics['passed'] ? 'PASS ✓' : 'FAIL ✗',
                sprintf('%.0f%% / %s', $metrics['coverage'] * 100, $metrics['citation'] ? 'cit ✓' : 'cit ✗'),
                $model,
            ];
        }

        $this->table(['ID', 'Kategori', 'Pertanyaan', 'Status', 'Coverage/Citation', 'Model'], $rows);

        $quality = $positiveTotal > 0 ? $positivePassed / $positiveTotal : 0.0;

        $this->newLine();
        $this->components->twoColumnDetail('Kasus positif lulus', sprintf('%d/%d (%.1f%%)', $positivePassed, $positiveTotal, $quality * 100));
        $this->components->twoColumnDetail('Kasus negatif ditolak', sprintf('%d/%d', $negativePassed, $negativeTotal));
        $this->components->twoColumnDetail('Ambang kualitas (PRD §6)', sprintf('>= %.0f%%', $qualityThreshold * 100));

        $passed = $quality >= $qualityThreshold && $negativePassed === $negativeTotal;

        if ($passed) {
            $this->components->success('GATE KB EVAL: LULUS — kualitas >= 90% dan semua kasus negatif ditolak.');
        } else {
            $this->components->error('GATE KB EVAL: GAGAL — lihat detail per kasus di atas.');
        }

        $this->newLine();
        $reviewed = $dataset['meta']['reviewed'] ?? null;

        if ($reviewed) {
            $this->components->info("Dataset sudah di-review konten ({$reviewed}) — layak sebagai bukti gate produksi (PRD §6).");
        } else {
            $this->components->warn('Dataset belum di-review (verify_human=false) — wajib review HR final sebelum dipakai sebagai bukti gate produksi (PRD §6).');
        }

        return $passed ? self::SUCCESS : self::FAILURE;
    }
}
