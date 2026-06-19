<?php

use App\Ai\Agents\HrKnowledgeBaseAgent;
use App\Enums\KnowledgeBaseCategory;
use App\Enums\KnowledgeBaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Jobs\ProcessKnowledgeBaseEmbedding;
use App\Models\KnowledgeBase;
use App\Models\User;
use App\Services\EmbeddingService;
use App\Services\GeminiClient;
use App\Services\KnowledgeBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $kbDir = storage_path('app/knowledgebase');
    if (! is_dir($kbDir)) {
        mkdir($kbDir, 0755, true);
    }
});

describe('chat', function () {
    it('throws BusinessRuleException for short question (< 5 chars)', function () {
        $svc = new KnowledgeBaseService(mock(GeminiClient::class), mock(EmbeddingService::class));

        expect(fn () => $svc->chat('abc'))
            ->toThrow(BusinessRuleException::class, '5-500 karakter');
    });

    it('throws BusinessRuleException for long question (> 500 chars)', function () {
        $svc = new KnowledgeBaseService(mock(GeminiClient::class), mock(EmbeddingService::class));

        expect(fn () => $svc->chat(str_repeat('a', 501)))
            ->toThrow(BusinessRuleException::class, '5-500 karakter');
    });

    it('returns answer with sources on vector search + Gemini success', function () {
        $gemini = mock(GeminiClient::class);
        $embedding = mock(EmbeddingService::class);

        $kb1 = KnowledgeBase::create([
            'knowledgeable_type' => KnowledgeBase::class,
            'knowledgeable_id' => 1,
            'title' => 'Cuti Tahunan',
            'content' => 'Karyawan berhak atas 12 hari cuti tahunan.',
            'category' => KnowledgeBaseCategory::HR_POLICY,
            'status' => KnowledgeBaseStatus::READY,
        ]);
        $kb2 = KnowledgeBase::create([
            'knowledgeable_type' => KnowledgeBase::class,
            'knowledgeable_id' => 1,
            'title' => 'Cuti Sakit',
            'content' => 'Cuti sakit dihitung terpisah dari cuti tahunan.',
            'category' => KnowledgeBaseCategory::HR_POLICY,
            'status' => KnowledgeBaseStatus::READY,
        ]);

        $gemini->shouldReceive('embed')->once()->andReturn(array_fill(0, 768, 0.1));
        $embedding->shouldReceive('searchSimilar')->once()->andReturn(new Collection([$kb1, $kb2]));

        HrKnowledgeBaseAgent::fake([[
            'answer' => 'Karyawan berhak atas 12 hari cuti tahunan.',
            'confidence' => 'high',
        ]]);

        $svc = new KnowledgeBaseService($gemini, $embedding);
        $result = $svc->chat('Apa itu cuti tahunan?');

        expect($result['answer'])->toBe('Karyawan berhak atas 12 hari cuti tahunan.');
        expect($result['sources'])->toHaveCount(2);
        expect($result['sources'][0]['title'])->toBe('Cuti Tahunan');
        expect($result['sources'][1]['title'])->toBe('Cuti Sakit');
        expect($result['fallback'])->toBeFalse();
        expect($result['model'])->toBe(config('services.gemini.model'));
    });

    it('falls back to keyword search when Gemini throws', function () {
        $gemini = mock(GeminiClient::class);
        $embedding = mock(EmbeddingService::class);

        $kb = KnowledgeBase::create([
            'knowledgeable_type' => KnowledgeBase::class,
            'knowledgeable_id' => 1,
            'title' => 'Cuti Tahunan',
            'content' => 'Karyawan berhak atas 12 hari cuti tahunan.',
            'category' => KnowledgeBaseCategory::HR_POLICY,
            'status' => KnowledgeBaseStatus::READY,
        ]);

        $gemini->shouldReceive('embed')->once()->andThrow(new Exception('Gemini API down'));
        $embedding->shouldReceive('searchByKeyword')->once()->andReturn(new Collection([$kb]));

        $svc = new KnowledgeBaseService($gemini, $embedding);
        $result = $svc->chat('Apa itu cuti tahunan?');

        expect($result['fallback'])->toBeTrue();
        expect($result['model'])->toBe('pg_trgm');
        expect($result['answer'])->toContain('offline');
        expect($result['sources'])->toHaveCount(1);
    });
});

describe('uploadPdf', function () {
    it('throws BusinessRuleException for non-PDF file', function () {
        $svc = new KnowledgeBaseService(mock(GeminiClient::class), mock(EmbeddingService::class));

        $txt = UploadedFile::fake()->create('test.txt', 1024, 'text/plain');

        expect(fn () => $svc->uploadPdf($txt, 'Test'))
            ->toThrow(BusinessRuleException::class, 'harus PDF');
    });

    it('throws BusinessRuleException for file over 10 MB', function () {
        $svc = new KnowledgeBaseService(mock(GeminiClient::class), mock(EmbeddingService::class));

        $pdf = UploadedFile::fake()->create('test.pdf', 11264, 'application/pdf');

        expect(fn () => $svc->uploadPdf($pdf, 'Test'))
            ->toThrow(BusinessRuleException::class, 'maksimal 10 MB');
    });

    it('dispatches ProcessKnowledgeBaseEmbedding job on success', function () {
        Queue::fake();

        $gemini = mock(GeminiClient::class);
        $embedding = mock(EmbeddingService::class);

        $embedding->shouldReceive('extractTextFromPdf')->once()->andReturn('Extracted text content for testing.');
        $embedding->shouldReceive('chunkText')->once()->andReturn(['Chunk one', 'Chunk two', 'Chunk three']);

        $pdf = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        $owner = User::factory()->create();

        $svc = new KnowledgeBaseService($gemini, $embedding);
        $result = $svc->uploadPdf($pdf, 'Test Document', KnowledgeBaseCategory::HR_POLICY, $owner);

        expect($result)->toBeInstanceOf(KnowledgeBase::class);
        expect($result->title)->toBe('Test Document');
        expect($result->knowledgeable_type)->toBe($owner::class);
        expect($result->knowledgeable_id)->toBe($owner->id);
        expect($result->category)->toBe(KnowledgeBaseCategory::HR_POLICY);
        expect($result->status)->toBe(KnowledgeBaseStatus::PROCESSING);
        expect($result->source_document)->toStartWith('kb_');
        expect($result->page_number)->toBe(0);

        Queue::assertPushed(ProcessKnowledgeBaseEmbedding::class, 3);
    });

    it('throws ValidationException when owner is missing', function () {
        $gemini = mock(GeminiClient::class);
        $embedding = mock(EmbeddingService::class);

        $pdf = UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');

        $svc = new KnowledgeBaseService($gemini, $embedding);

        expect(fn () => $svc->uploadPdf($pdf, 'Test Document', KnowledgeBaseCategory::HR_POLICY))
            ->toThrow(ValidationException::class);
    });
});

describe('reindex', function () {
    it('sets status to PROCESSING and dispatches embedding job', function () {
        Queue::fake();

        $kb = KnowledgeBase::create([
            'knowledgeable_type' => KnowledgeBase::class,
            'knowledgeable_id' => 1,
            'title' => 'HR Policy',
            'content' => 'Some policy content.',
            'category' => KnowledgeBaseCategory::GENERAL,
            'status' => KnowledgeBaseStatus::ERROR,
        ]);

        $svc = new KnowledgeBaseService(mock(GeminiClient::class), mock(EmbeddingService::class));
        $svc->reindex($kb);

        expect($kb->fresh()->status)->toBe(KnowledgeBaseStatus::PROCESSING);
        Queue::assertPushed(ProcessKnowledgeBaseEmbedding::class, 1);
    });
});

describe('deleteKnowledgeBase', function () {
    it('deletes all records sharing the same source_document', function () {
        $kb1 = KnowledgeBase::create([
            'knowledgeable_type' => KnowledgeBase::class,
            'knowledgeable_id' => 1,
            'title' => 'Doc 1 Chunk 1',
            'content' => 'Chunk content 1',
            'source_document' => 'doc1.pdf',
            'category' => KnowledgeBaseCategory::GENERAL,
            'status' => KnowledgeBaseStatus::READY,
        ]);
        KnowledgeBase::create([
            'knowledgeable_type' => KnowledgeBase::class,
            'knowledgeable_id' => 1,
            'title' => 'Doc 1 Chunk 2',
            'content' => 'Chunk content 2',
            'source_document' => 'doc1.pdf',
            'category' => KnowledgeBaseCategory::GENERAL,
            'status' => KnowledgeBaseStatus::READY,
        ]);
        $kbOther = KnowledgeBase::create([
            'knowledgeable_type' => KnowledgeBase::class,
            'knowledgeable_id' => 1,
            'title' => 'Doc 2',
            'content' => 'Other document.',
            'source_document' => 'doc2.pdf',
            'category' => KnowledgeBaseCategory::GENERAL,
            'status' => KnowledgeBaseStatus::READY,
        ]);

        $svc = new KnowledgeBaseService(mock(GeminiClient::class), mock(EmbeddingService::class));
        $count = $svc->deleteKnowledgeBase($kb1);

        expect($count)->toBe(2);
        expect(KnowledgeBase::count())->toBe(1);
        expect(KnowledgeBase::first()->id)->toBe($kbOther->id);
    });

    it('deletes single record when source_document is null', function () {
        $kb1 = KnowledgeBase::create([
            'knowledgeable_type' => KnowledgeBase::class,
            'knowledgeable_id' => 1,
            'title' => 'Standalone',
            'content' => 'No source document.',
            'category' => KnowledgeBaseCategory::GENERAL,
            'status' => KnowledgeBaseStatus::READY,
        ]);
        $kb2 = KnowledgeBase::create([
            'knowledgeable_type' => KnowledgeBase::class,
            'knowledgeable_id' => 1,
            'title' => 'Other',
            'content' => 'Other record.',
            'category' => KnowledgeBaseCategory::GENERAL,
            'status' => KnowledgeBaseStatus::READY,
        ]);

        $svc = new KnowledgeBaseService(mock(GeminiClient::class), mock(EmbeddingService::class));
        $count = $svc->deleteKnowledgeBase($kb1);

        expect($count)->toBe(1);
        expect(KnowledgeBase::count())->toBe(1);
        expect(KnowledgeBase::first()->id)->toBe($kb2->id);
    });
});
