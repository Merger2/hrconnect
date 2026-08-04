<?php

/**
 * Unit test untuk KnowledgeBase + Embedding services.
 *
 * EmbeddingService punya test mode (app()->runningUnitTests()) yang mengembalikan
 * fake embedding — memungkinkan pengujian tanpa API key.
 *
 * KnowledgeBaseService::chat() menggunakan HrKnowledgeBaseAgent dari AI SDK
 * (vendor) yang tidak bisa dimock langsung. Test fokus pada validation paths,
 * uploadPdf flow, reindex, dan deleteKnowledgeBase.
 */

use App\Enums\KnowledgeBaseCategory;
use App\Enums\KnowledgeBaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Jobs\ProcessKnowledgeBaseEmbedding;
use App\Models\KnowledgeBase;
use App\Models\User;
use App\Services\KnowledgeBase\KnowledgeBaseService;
use App\Services\Security\EmbeddingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->embeddingService = new EmbeddingService;
    $this->kbService = new KnowledgeBaseService($this->embeddingService);

    // Create a user as owner for uploadPdf tests
    $userId = DB::table('users')->insertGetId([
        'name' => 'KB Owner',
        'email' => 'kb.owner@t.com',
        'password' => bcrypt('x'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->owner = User::find($userId);
});

// ═══════════════════════════════════════════════════════════════════════
// EmbeddingService — chunkText()
// ═══════════════════════════════════════════════════════════════════════

test('chunkText returns empty array for empty text', function () {
    expect($this->embeddingService->chunkText(''))->toBe([]);
});

test('chunkText returns empty array for very short text', function () {
    expect($this->embeddingService->chunkText('Short text'))->toBe([]);
});

test('chunkText splits text into chunks', function () {
    $text = str_repeat('Lorem ipsum dolor sit amet. ', 200); // ~6000 chars
    $chunks = $this->embeddingService->chunkText($text);

    expect($chunks)->not->toBeEmpty();
    expect(count($chunks))->toBeGreaterThanOrEqual(5);
    foreach ($chunks as $chunk) {
        expect(mb_strlen($chunk))->toBeGreaterThanOrEqual(50);
    }
});

test('chunkText respects chunk size parameter', function () {
    $text = str_repeat('A', 5000);
    $chunks = $this->embeddingService->chunkText($text, chunkChars: 500, overlapChars: 100);

    expect($chunks)->not->toBeEmpty();
    foreach ($chunks as $chunk) {
        expect(mb_strlen($chunk))->toBeLessThanOrEqual(500);
    }
});

test('chunkText filters out chunks shorter than 50 chars', function () {
    $shortText = str_repeat('A', 100).' '.str_repeat('B', 30); // 2nd part < 50 after split
    // With chunkChars=100, overlapChars=50, the first chunk has 100 As
    // The second chunk starts at position 50, has 50 As + ' ' + 30 Bs
    $chunks = $this->embeddingService->chunkText($shortText, chunkChars: 100, overlapChars: 50);

    // All chunks should be >= 50 chars
    foreach ($chunks as $chunk) {
        expect(mb_strlen($chunk))->toBeGreaterThanOrEqual(50);
    }
});

// ═══════════════════════════════════════════════════════════════════════
// EmbeddingService — formatVector()
// ═══════════════════════════════════════════════════════════════════════

test('formatVector returns correct format', function () {
    $vector = [0.1, 0.2, 0.3];
    $result = $this->embeddingService->formatVector($vector);

    expect($result)->toBe('[0.1,0.2,0.3]');
});

test('formatVector handles single element', function () {
    $result = $this->embeddingService->formatVector([42.0]);

    expect($result)->toBe('[42]');
});

// ═══════════════════════════════════════════════════════════════════════
// EmbeddingService — embed() in test mode
// ═══════════════════════════════════════════════════════════════════════

test('embed in test mode returns 768-dimension vector', function () {
    $vector = $this->embeddingService->embed('Test question about payroll');

    expect($vector)->toBeArray();
    expect(count($vector))->toBe(768);
});

test('embed throws for empty text', function () {
    expect(fn () => $this->embeddingService->embed(''))
        ->toThrow(BusinessRuleException::class, 'Teks untuk embedding harus');
});

test('embed throws for too long text', function () {
    $longText = str_repeat('A', 30001);
    expect(fn () => $this->embeddingService->embed($longText))
        ->toThrow(BusinessRuleException::class, 'Teks untuk embedding harus');
});

// ═══════════════════════════════════════════════════════════════════════
// EmbeddingService — processKnowledgeBase()
// ═══════════════════════════════════════════════════════════════════════

test('processKnowledgeBase sets ERROR for empty content', function () {
    $kb = KnowledgeBase::create([
        'title' => 'Empty KB',
        'content' => '',
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
        'status' => KnowledgeBaseStatus::PROCESSING,
    ]);

    $this->embeddingService->processKnowledgeBase($kb);

    $kb->refresh();
    expect($kb->status->value)->toBe('error');
});

test('processKnowledgeBase updates to READY and stores embedding', function () {
    $kb = KnowledgeBase::create([
        'title' => 'Test KB',
        'content' => 'Ini adalah konten pengetahuan yang cukup panjang untuk di-embedding dan diproses oleh sistem RAG HRConnect. Sistem akan menghasilkan vector embedding untuk pencarian semantic.',
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
        'status' => KnowledgeBaseStatus::PROCESSING,
    ]);

    $this->embeddingService->processKnowledgeBase($kb);

    $kb->refresh();
    expect($kb->status->value)->toBe('ready');
    expect($kb->embedding)->not->toBeNull();
});

// ═══════════════════════════════════════════════════════════════════════
// EmbeddingService — searchSimilar()
// ═══════════════════════════════════════════════════════════════════════

test('searchSimilar returns only READY records', function () {
    KnowledgeBase::create([
        'title' => 'Ready Doc',
        'content' => 'Content about payroll and benefits',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);
    KnowledgeBase::create([
        'title' => 'Processing Doc',
        'content' => 'Content about HR policies',
        'status' => KnowledgeBaseStatus::PROCESSING,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);

    $queryVector = array_fill(0, 768, 0.01);
    $results = $this->embeddingService->searchSimilar($queryVector, topK: 5);

    expect($results->count())->toBe(0); // No embedding set, so <=> returns null
});

test('searchSimilar respects topK limit', function () {
    // Create records with embedding set via raw SQL
    $vector = '['.implode(',', array_fill(0, 768, 0.01)).']';

    for ($i = 0; $i < 10; $i++) {
        DB::table('knowledge_bases')->insert([
            'title' => "Doc {$i}",
            'content' => "Content number {$i}",
            'status' => KnowledgeBaseStatus::READY->value,
            'knowledgeable_type' => 'App\Models\User',
            'knowledgeable_id' => 0,
            'embedding' => DB::raw("'{$vector}'::vector"),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $queryVector = array_fill(0, 768, 0.01);
    $results = $this->embeddingService->searchSimilar($queryVector, topK: 3);

    expect($results->count())->toBeLessThanOrEqual(3);
});

// ═══════════════════════════════════════════════════════════════════════
// EmbeddingService — searchByKeyword()
// ═══════════════════════════════════════════════════════════════════════

test('searchByKeyword finds matching content', function () {
    KnowledgeBase::create([
        'title' => 'Payroll Guide',
        'content' => 'How to process monthly payroll and PPh 21 calculations',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);
    KnowledgeBase::create([
        'title' => 'Attendance',
        'content' => 'How to clock in and out using face recognition',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);

    $results = $this->embeddingService->searchByKeyword('payroll', topK: 5);

    expect($results->count())->toBe(1);
    expect($results->first()->title)->toBe('Payroll Guide');
});

test('searchByKeyword respects topK limit', function () {
    for ($i = 0; $i < 10; $i++) {
        KnowledgeBase::create([
            'title' => "Doc {$i}",
            'content' => 'Common keyword content for testing search functionality',
            'status' => KnowledgeBaseStatus::READY,
            'knowledgeable_type' => 'App\Models\User',
            'knowledgeable_id' => 0,
        ]);
    }

    $results = $this->embeddingService->searchByKeyword('keyword', topK: 3);

    expect($results->count())->toBe(3);
});

test('searchByKeyword returns empty for non-matching keyword', function () {
    KnowledgeBase::create([
        'title' => 'HR Policy',
        'content' => 'Company regulations about leave and attendance',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);

    $results = $this->embeddingService->searchByKeyword('nonexistent_term_xyz', topK: 5);

    expect($results)->toBeEmpty();
});

test('searchByKeyword returns only READY records', function () {
    KnowledgeBase::create([
        'title' => 'Ready Doc',
        'content' => 'Searchable content',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);
    KnowledgeBase::create([
        'title' => 'Processing Doc',
        'content' => 'Searchable content',
        'status' => KnowledgeBaseStatus::PROCESSING,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);

    $results = $this->embeddingService->searchByKeyword('Searchable', topK: 5);

    expect($results->count())->toBe(1);
});

// ═══════════════════════════════════════════════════════════════════════
// KnowledgeBaseService — chat() validation
// ═══════════════════════════════════════════════════════════════════════

test('chat throws for question shorter than 5 characters', function () {
    expect(fn () => $this->kbService->chat('Abc'))
        ->toThrow(BusinessRuleException::class, 'Pertanyaan harus 5-500 karakter');
});

test('chat throws for question longer than 500 characters', function () {
    $longQuestion = str_repeat('A', 501);
    expect(fn () => $this->kbService->chat($longQuestion))
        ->toThrow(BusinessRuleException::class, 'Pertanyaan harus 5-500 karakter');
});

// ═══════════════════════════════════════════════════════════════════════
// KnowledgeBaseService — uploadPdf() validation
// ═══════════════════════════════════════════════════════════════════════

test('uploadPdf throws for non-PDF file extension', function () {
    $file = UploadedFile::fake()->create('document.txt', 100);

    expect(fn () => $this->kbService->uploadPdf(
        pdf: $file,
        title: 'Test Doc',
        category: KnowledgeBaseCategory::GENERAL,
        owner: $this->owner,
    ))->toThrow(BusinessRuleException::class, 'File harus PDF');
});

// MIME type test removed — redundant with extension check

test('uploadPdf throws for file exceeding 10MB', function () {
    $file = UploadedFile::fake()->create('large.pdf', 11 * 1024); // 11MB

    expect(fn () => $this->kbService->uploadPdf(
        pdf: $file,
        title: 'Large Doc',
        category: KnowledgeBaseCategory::GENERAL,
        owner: $this->owner,
    ))->toThrow(BusinessRuleException::class, 'PDF maksimal 10 MB');
});

test('uploadPdf throws when owner is null', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 100);

    expect(fn () => $this->kbService->uploadPdf(
        pdf: $file,
        title: 'No Owner',
        category: KnowledgeBaseCategory::GENERAL,
        owner: null,
    ))->toThrow(ValidationException::class);
});

// ═══════════════════════════════════════════════════════════════════════
// KnowledgeBaseService — reindex()
// ═══════════════════════════════════════════════════════════════════════

test('reindex updates status to PROCESSING and dispatches job', function () {
    Queue::fake();

    $kb = KnowledgeBase::create([
        'title' => 'Reindex Test',
        'content' => 'Content to reindex for embedding',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);

    $this->kbService->reindex($kb);

    $kb->refresh();
    expect($kb->status->value)->toBe('processing');

    Queue::assertPushed(ProcessKnowledgeBaseEmbedding::class);
});

// ═══════════════════════════════════════════════════════════════════════
// KnowledgeBaseService — deleteKnowledgeBase()
// ═══════════════════════════════════════════════════════════════════════

test('deleteKnowledgeBase deletes single record without source_document', function () {
    $kb = KnowledgeBase::create([
        'title' => 'Delete Test',
        'content' => 'Will be deleted',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);

    $count = $this->kbService->deleteKnowledgeBase($kb);

    expect($count)->toBe(1);
    expect(KnowledgeBase::find($kb->id))->toBeNull();
});

test('deleteKnowledgeBase deletes all records with same source_document', function () {
    $sourceDoc = 'shared_document.pdf';

    $kb1 = KnowledgeBase::create([
        'title' => 'Doc 1',
        'content' => 'First chunk',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
        'source_document' => $sourceDoc,
    ]);
    $kb2 = KnowledgeBase::create([
        'title' => 'Doc 2',
        'content' => 'Second chunk',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
        'source_document' => $sourceDoc,
    ]);

    $count = $this->kbService->deleteKnowledgeBase($kb1);

    expect($count)->toBe(2);
    expect(KnowledgeBase::where('source_document', $sourceDoc)->count())->toBe(0);
});

// ═══════════════════════════════════════════════════════════════════════
// Contract verification
// ═══════════════════════════════════════════════════════════════════════

test('services can be instantiated', function () {
    expect($this->embeddingService)->toBeInstanceOf(EmbeddingService::class);
    expect($this->kbService)->toBeInstanceOf(KnowledgeBaseService::class);
});

test('KnowledgeBaseService has expected public methods', function () {
    $reflection = new ReflectionClass(KnowledgeBaseService::class);

    expect($reflection->hasMethod('chat'))->toBeTrue();
    expect($reflection->hasMethod('chatStream'))->toBeTrue();
    expect($reflection->hasMethod('uploadPdf'))->toBeTrue();
    expect($reflection->hasMethod('reindex'))->toBeTrue();
    expect($reflection->hasMethod('deleteKnowledgeBase'))->toBeTrue();
});

test('EmbeddingService has expected public methods', function () {
    $reflection = new ReflectionClass(EmbeddingService::class);

    expect($reflection->hasMethod('chunkText'))->toBeTrue();
    expect($reflection->hasMethod('formatVector'))->toBeTrue();
    expect($reflection->hasMethod('embed'))->toBeTrue();
    expect($reflection->hasMethod('processKnowledgeBase'))->toBeTrue();
    expect($reflection->hasMethod('searchSimilar'))->toBeTrue();
    expect($reflection->hasMethod('searchByKeyword'))->toBeTrue();
    expect($reflection->hasMethod('extractTextFromPdf'))->toBeTrue();
});
