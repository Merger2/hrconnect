<?php

use App\Exceptions\BusinessRuleException;
use App\Services\EmbeddingService;
use App\Services\GeminiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Unit tests untuk EmbeddingService — fokus pada chunking + format vector.
 *
 * Real PDF parsing + Gemini API di-mock atau di-skip karena butuh dependency
 * eksternal (Chromium/Puppeteer untuk Spatie PDF, Gemini API key, etc).
 */
test('chunkText split sesuai chunk size dan overlap', function () {
    $svc = new EmbeddingService(new GeminiClient(mockMode: true));

    $text = str_repeat('Karyawan PT 521 Teknologi mendapat fasilitas BPJS lengkap. ', 20);
    $chunks = $svc->chunkText($text, chunkChars: 100, overlapChars: 20);

    expect($chunks)->toBeArray();
    expect(count($chunks))->toBeGreaterThan(2);

    foreach ($chunks as $chunk) {
        expect(mb_strlen($chunk))->toBeLessThanOrEqual(100);
    }
});

test('chunkText skip chunk yang terlalu pendek', function () {
    $svc = new EmbeddingService(new GeminiClient(mockMode: true));

    $tinyText = 'Hello'; // 5 karakter, di bawah threshold 20
    expect($svc->chunkText($tinyText))->toBe([]);
});

test('chunkText handle text kosong return empty array', function () {
    $svc = new EmbeddingService(new GeminiClient(mockMode: true));

    expect($svc->chunkText(''))->toBe([]);
    expect($svc->chunkText('   '))->toBe([]);
});

test('formatVector return string format pgvector', function () {
    $svc = new EmbeddingService(new GeminiClient(mockMode: true));

    $vector = [0.1, -0.2, 0.3];
    expect($svc->formatVector($vector))->toBe('[0.1,-0.2,0.3]');
});

test('formatVector handle 768D vector', function () {
    $svc = new EmbeddingService(new GeminiClient(mockMode: true));

    $vector = array_fill(0, 768, 0.1);
    $result = $svc->formatVector($vector);

    expect($result)->toStartWith('[0.1');
    expect(substr_count($result, ','))->toBe(767);
});

test('extractTextFromPdf throw kalau file tidak ada', function () {
    $svc = new EmbeddingService(new GeminiClient(mockMode: true));

    expect(fn () => $svc->extractTextFromPdf('/nonexistent/file.pdf'))
        ->toThrow(BusinessRuleException::class, 'tidak dapat dibaca');
});

test('processKnowledgeBase set status error kalau content kosong', function () {
    $svc = new EmbeddingService(new GeminiClient(mockMode: true));

    $kb = \App\Models\KnowledgeBase::factory()->create();
    $kb->update(['content' => '']);

    $svc->processKnowledgeBase($kb);

    expect($kb->fresh()->status->value)->toBe('error');
});

test('processKnowledgeBase set status ready on success', function () {
    $svc = new EmbeddingService(new GeminiClient(mockMode: true));

    $kb = \App\Models\KnowledgeBase::factory()->create([
        'content' => 'Some test content for embedding.',
    ]);

    $svc->processKnowledgeBase($kb);

    expect($kb->fresh()->status->value)->toBe('ready');
});

test('searchSimilar returns ready records in SQLite (fallback)', function () {
    $svc = new EmbeddingService(new GeminiClient(mockMode: true));

    \App\Models\KnowledgeBase::factory()->count(3)->create(['status' => \App\Enums\KnowledgeBaseStatus::READY]);
    \App\Models\KnowledgeBase::factory()->create(['status' => \App\Enums\KnowledgeBaseStatus::ERROR]);

    $results = $svc->searchSimilar([0.1, 0.2, 0.3]);

    expect($results)->toHaveCount(3);
});

test('searchByKeyword uses like fallback in SQLite', function () {
    $svc = new EmbeddingService(new GeminiClient(mockMode: true));

    \App\Models\KnowledgeBase::factory()->create([
        'content' => 'BPJS Kesehatan dan BPJS Ketenagakerjaan',
        'status' => \App\Enums\KnowledgeBaseStatus::READY,
    ]);

    $results = $svc->searchByKeyword('BPJS');

    expect($results)->toHaveCount(1);
});
