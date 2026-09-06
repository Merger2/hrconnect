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
use Illuminate\Support\Facades\Http;
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
        'content' => 'Ini adalah konten pengetahuan yang cukup panjang untuk di-embedding dan diproses oleh sistem RAG perusahaan. Sistem akan menghasilkan vector embedding untuk pencarian semantic.',
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

test('searchSimilar throws for wrong dimension query vector (mock-miss fix)', function () {
    // Sebelumnya: diam-diam memakai fake embedding random → retrieval sampah.
    // Kini dimensi salah = gagal keras (no silent degradation / jangan fake).
    $queryVector = array_fill(0, 10, 0.01);

    expect(fn () => $this->embeddingService->searchSimilar($queryVector))
        ->toThrow(BusinessRuleException::class, 'Query vector untuk pencarian semantik harus 768D');
});

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
// searchByKeyword — tokenization & scoring (fix bug 2026-08-05)
// ═══════════════════════════════════════════════════════════════════════

test('searchByKeyword tokenizes natural long questions and finds matching chunks', function () {
    KnowledgeBase::create([
        'title' => 'Sanksi Keterlambatan',
        'content' => 'Keterlambatan lebih dari 15 menit tanpa pemberitahuan akan dicatat sebagai late. Akumulasi keterlambatan dapat mempengaruhi penilaian kinerja bulanan.',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);

    // Kalimat penuh user (bug report) — sebelumnya ILIKE %kalimat utuh% tidak pernah match.
    $results = $this->embeddingService->searchByKeyword('bagaimana keterlambatan kerja di perusahaan ini', topK: 5);

    expect($results->count())->toBe(1);
    expect($results->first()->title)->toBe('Sanksi Keterlambatan');
    expect($results->first()->content)->toContain('Keterlambatan');
});

test('searchByKeyword drops short tokens and common stopwords', function () {
    KnowledgeBase::create([
        'title' => 'Doc A',
        'content' => 'isi kebijakan yang sudah lama',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);
    KnowledgeBase::create([
        'title' => 'Doc B',
        'content' => 'isi kebijakan lembur malam',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);

    // "di" (< 3 char), "yang"/"sudah" (stopword) dibuang → token "kebijakan" tetap dicari.
    $results = $this->embeddingService->searchByKeyword('di yang sudah kebijakan', topK: 5);

    expect($results->count())->toBe(2);

    // Hanya stopword/short token → tidak ada token yang layak → hasil kosong (bukan error).
    $empty = $this->embeddingService->searchByKeyword('di yang', topK: 5);

    expect($empty)->toBeEmpty();
});

test('searchByKeyword strips punctuation from tokens', function () {
    KnowledgeBase::create([
        'title' => 'Aturan Lembur',
        'content' => 'Lembur maksimal 3 jam per hari dan 14 jam per minggu.',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);

    $results = $this->embeddingService->searchByKeyword('lembur?', topK: 5);

    expect($results->count())->toBe(1);
    expect($results->first()->title)->toBe('Aturan Lembur');
});

test('searchByKeyword ranks chunks by number of matched tokens', function () {
    KnowledgeBase::create([
        'title' => 'Dokumen Gaji dan Keterlambatan',
        'content' => 'gaji pokok dan keterlambatan karyawan',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);
    KnowledgeBase::create([
        'title' => 'Dokumen Gaji',
        'content' => 'gaji pokok karyawan saja',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);

    // Token: gaji, keterlambatan, karyawan → Doc A match 3, Doc B match 2.
    $results = $this->embeddingService->searchByKeyword('gaji keterlambatan karyawan', topK: 5);

    expect($results->count())->toBe(2);
    expect($results->first()->title)->toBe('Dokumen Gaji dan Keterlambatan');
    expect($results->last()->title)->toBe('Dokumen Gaji');
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

// ═══════════════════════════════════════════════════════════════════════
// chatStream — fallback pg_trgm (fix bug 2026-08-05: pesan jelas + flag)
// ═══════════════════════════════════════════════════════════════════════

test('chatStream fallback returns keyword snippets when chunks found', function () {
    // Simulasi Gemini down → pipeline mengambil jalur fallback nyata (searchByKeyword).
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 401)]);

    KnowledgeBase::create([
        'title' => 'Sanksi Keterlambatan',
        'content' => 'Keterlambatan lebih dari 15 menit tanpa pemberitahuan akan dicatat sebagai late. Akumulasi keterlambatan dapat mempengaruhi penilaian kinerja bulanan.',
        'status' => KnowledgeBaseStatus::READY,
        'knowledgeable_type' => 'App\Models\User',
        'knowledgeable_id' => 0,
    ]);

    $yields = iterator_to_array($this->kbService->chatStream('bagaimana keterlambatan kerja di perusahaan ini'));

    $texts = collect($yields)->pluck('text')->filter()->implode("\n");

    expect($texts)->toContain('asisten AI sedang tidak tersedia saat ini');
    expect($texts)->toContain('Keterlambatan lebih dari 15 menit');
    expect($texts)->toContain('[Sumber 1]');

    $last = $yields[array_key_last($yields)];

    expect($last['fallback'] ?? false)->toBeTrue();
    expect($last['no_results'] ?? false)->toBeFalse();
    expect($last['sources'][0]['title'])->toBe('Sanksi Keterlambatan');
});

test('is_greeting_question detects pure greetings only', function () {
    expect(is_greeting_question('halo selamat sore'))->toBeTrue();
    expect(is_greeting_question('halo'))->toBeTrue();
    expect(is_greeting_question('terima kasih'))->toBeTrue();
    expect(is_greeting_question('apa kabar'))->toBeTrue();
    expect(is_greeting_question('halo, bagaimana cara cuti?'))->toBeFalse();
    expect(is_greeting_question('apa itu cuti tahunan?'))->toBeFalse();
    expect(is_greeting_question('selamat sore min'))->toBeTrue();
    expect(is_greeting_question(''))->toBeFalse();
});

test('chat greeting answers without sources (no leak of unrelated docs)', function () {
    // Gemini gagal (401) → greetingResponse jatuh ke balasan statis ramah,
    // TAPI tetap tanpa sources — dokumen tidak relevan tidak pernah tampil
    // saat user sekadar menyapa.
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 401)]);

    $result = $this->kbService->chat('halo selamat sore');

    expect($result['sources'])->toBe([]);
    expect($result['answer'])->toContain('Halo!');
});

test('chat allows short greetings but still rejects non-greeting short text', function () {
    // 'halo' (4 char) = greeting → diizinkan; jalur greeting memakai fake 401
    // sehingga agent gagal → balasan statis tanpa sources.
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 401)]);

    $greeting = $this->kbService->chat('halo');

    expect($greeting['sources'])->toBe([]);

    // 'Abc' bukan greeting → tetap tolak (validasi 5-500).
    expect(fn () => $this->kbService->chat('Abc'))
        ->toThrow(BusinessRuleException::class, 'Pertanyaan harus 5-500 karakter');
});

test('chatStream fallback reports no relevant results honestly', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 401)]);

    // Corpus kosong → tidak ada token yang match → pesan no_results (bukan
    // "coba lagi nanti" yang menyesatkan — masalahnya retrieval, bukan cuma AI).
    $yields = iterator_to_array($this->kbService->chatStream('premi asuransi jiwa'));

    $texts = collect($yields)->pluck('text')->filter()->implode("\n");

    expect($texts)->toContain('tidak ditemukan informasi yang relevan');
    expect($texts)->not->toContain('Silakan coba lagi nanti');

    $last = $yields[array_key_last($yields)];

    expect($last['fallback'] ?? false)->toBeTrue();
    expect($last['no_results'] ?? false)->toBeTrue();
});
