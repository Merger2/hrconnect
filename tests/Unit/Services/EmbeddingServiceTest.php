<?php

use App\Enums\KnowledgeBaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\KnowledgeBase;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Embeddings;

uses(RefreshDatabase::class);

beforeEach(function () {
    Embeddings::fake();
});

test('chunkText split sesuai chunk size dan overlap', function () {
    $svc = app(EmbeddingService::class);

    $text = str_repeat('Karyawan PT 521 Teknologi mendapat fasilitas BPJS lengkap. ', 20);
    $chunks = $svc->chunkText($text, chunkChars: 100, overlapChars: 20);

    expect($chunks)->toBeArray();
    expect(count($chunks))->toBeGreaterThan(2);

    foreach ($chunks as $chunk) {
        expect(mb_strlen($chunk))->toBeLessThanOrEqual(100);
    }
});

test('chunkText skip chunk yang terlalu pendek', function () {
    $svc = app(EmbeddingService::class);

    $tinyText = 'Hello';
    expect($svc->chunkText($tinyText))->toBe([]);
});

test('chunkText handle text kosong return empty array', function () {
    $svc = app(EmbeddingService::class);

    expect($svc->chunkText(''))->toBe([]);
    expect($svc->chunkText('   '))->toBe([]);
});

test('formatVector return string format pgvector', function () {
    $svc = app(EmbeddingService::class);

    $vector = [0.1, -0.2, 0.3];
    expect($svc->formatVector($vector))->toBe('[0.1,-0.2,0.3]');
});

test('formatVector handle 768D vector', function () {
    $svc = app(EmbeddingService::class);

    $vector = array_fill(0, 768, 0.1);
    $result = $svc->formatVector($vector);

    expect($result)->toStartWith('[0.1');
    expect(substr_count($result, ','))->toBe(767);
});

test('extractTextFromPdf throw kalau file tidak ada', function () {
    $svc = app(EmbeddingService::class);

    expect(fn () => $svc->extractTextFromPdf('/nonexistent/file.pdf'))
        ->toThrow(BusinessRuleException::class, 'tidak dapat dibaca');
});

test('processKnowledgeBase set status error kalau content kosong', function () {
    $svc = app(EmbeddingService::class);

    $kb = KnowledgeBase::factory()->create();
    $kb->update(['content' => '']);

    $svc->processKnowledgeBase($kb);

    expect($kb->fresh()->status->value)->toBe('error');
});

test('processKnowledgeBase set status ready on success', function () {
    $svc = app(EmbeddingService::class);

    $kb = KnowledgeBase::factory()->create([
        'content' => 'Some test content for embedding.',
    ]);

    $svc->processKnowledgeBase($kb);

    expect($kb->fresh()->status->value)->toBe('ready');
});

test('searchSimilar returns ready records in SQLite (fallback)', function () {
    $svc = app(EmbeddingService::class);

    KnowledgeBase::factory()->count(3)->create(['status' => KnowledgeBaseStatus::READY]);
    KnowledgeBase::factory()->create(['status' => KnowledgeBaseStatus::ERROR]);

    $results = $svc->searchSimilar([0.1, 0.2, 0.3]);

    expect($results)->toHaveCount(3);
});

test('searchByKeyword uses like fallback in SQLite', function () {
    $svc = app(EmbeddingService::class);

    KnowledgeBase::factory()->create([
        'content' => 'BPJS Kesehatan dan BPJS Ketenagakerjaan',
        'status' => KnowledgeBaseStatus::READY,
    ]);

    $results = $svc->searchByKeyword('BPJS');

    expect($results)->toHaveCount(1);
});

test('searchByKeyword returns empty hasil kalau tidak ada kecocokan', function () {
    $svc = app(EmbeddingService::class);

    KnowledgeBase::factory()->create([
        'content' => 'Cuti tahunan karyawan adalah 12 hari.',
        'status' => KnowledgeBaseStatus::READY,
    ]);

    $results = $svc->searchByKeyword('pajak');

    expect($results)->toBeEmpty();
});

test('searchSimilar respects topK parameter in SQLite fallback', function () {
    $svc = app(EmbeddingService::class);

    KnowledgeBase::factory()->count(5)->create(['status' => KnowledgeBaseStatus::READY]);

    $results = $svc->searchSimilar([0.1, 0.2, 0.3], topK: 2);

    expect($results)->toHaveCount(2);
});

test('processKnowledgeBase stores embedding vector content on success', function () {
    $svc = app(EmbeddingService::class);

    $kb = KnowledgeBase::factory()->create([
        'content' => 'BPJS Kesehatan mencakup layanan rawat inap dan rawat jalan.',
    ]);
    $kb->update(['embedding' => null]);

    $svc->processKnowledgeBase($kb);

    $fresh = $kb->fresh();
    expect($fresh->status->value)->toBe('ready');
    expect($fresh->embedding)->not->toBeNull();
});

test('processKnowledgeBase sets error status when embedding fails', function () {
    Embeddings::fake(fn () => throw new RuntimeException('API timeout'));

    $svc = app(EmbeddingService::class);

    $kb = KnowledgeBase::factory()->create([
        'content' => 'Test content that will fail embedding.',
    ]);

    expect(fn () => $svc->processKnowledgeBase($kb))
        ->toThrow(RuntimeException::class);
    expect($kb->fresh()->status->value)->toBe('error');
});

test('end-to-end: processKnowledgeBase then searchSimilar finds the record', function () {
    $svc = app(EmbeddingService::class);

    KnowledgeBase::factory()->create([
        'content' => 'BPJS Ketenagakerjaan meliputi JHT, JKK, JK, dan JP.',
        'status' => KnowledgeBaseStatus::PROCESSING,
    ]);

    $kb = KnowledgeBase::where('status', KnowledgeBaseStatus::PROCESSING)->first();
    $svc->processKnowledgeBase($kb);

    $results = $svc->searchSimilar([0.1, 0.2, 0.3]);
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($kb->id);
    expect($results->first()->status->value)->toBe('ready');
});

test('embed returns 768D vector via SDK fake', function () {
    $svc = app(EmbeddingService::class);

    $vector = $svc->embed('Test pertanyaan');

    expect($vector)->toBeArray();
    expect(count($vector))->toBe(768);
    foreach ($vector as $v) {
        expect($v)->toBeFloat();
    }
});

test('embed reject teks kosong', function () {
    $svc = app(EmbeddingService::class);

    expect(fn () => $svc->embed(''))
        ->toThrow(BusinessRuleException::class, 'harus 1-30000 karakter');
});

test('embed reject teks terlalu panjang (> 30000 char)', function () {
    $svc = app(EmbeddingService::class);

    $longText = str_repeat('a', 30_001);

    expect(fn () => $svc->embed($longText))
        ->toThrow(BusinessRuleException::class, 'harus 1-30000 karakter');
});
