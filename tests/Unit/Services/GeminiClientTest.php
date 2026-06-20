<?php

use App\Ai\Agents\HrKnowledgeBaseAgent;
use App\Exceptions\BusinessRuleException;
use App\Services\GeminiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Embeddings;

uses(RefreshDatabase::class);

beforeEach(function () {
    Embeddings::fake();
});

test('embed returns 768D vector via SDK fake', function () {
    $client = new GeminiClient;

    $vector = $client->embed('Test pertanyaan');

    expect($vector)->toBeArray();
    expect(count($vector))->toBe(768);
    foreach ($vector as $v) {
        expect($v)->toBeFloat();
    }
});

test('generateContent returns answer via SDK fake', function () {
    HrKnowledgeBaseAgent::fake([[
        'answer' => 'Cuti tahunan karyawan adalah 12 hari.',
        'confidence' => 'high',
    ]]);

    $client = new GeminiClient;

    $answer = $client->generateContent('Berapa cuti tahunan?', [
        ['content' => 'Cuti tahunan karyawan adalah 12 hari', 'source' => 'Employee Handbook'],
    ]);

    expect($answer)->toBe('Cuti tahunan karyawan adalah 12 hari.');
});

test('embed reject teks kosong', function () {
    $client = new GeminiClient;

    expect(fn () => $client->embed(''))
        ->toThrow(BusinessRuleException::class, 'harus 1-30000 karakter');
});

test('embed reject teks terlalu panjang (> 30000 char)', function () {
    $client = new GeminiClient;

    $longText = str_repeat('a', 30_001);

    expect(fn () => $client->embed($longText))
        ->toThrow(BusinessRuleException::class, 'harus 1-30000 karakter');
});

test('isHealthy return false ketika agent gagal', function () {
    HrKnowledgeBaseAgent::fake(fn () => throw new RuntimeException('API down'));

    $client = new GeminiClient;

    expect($client->isHealthy())->toBeFalse();
});

test('isHealthy return true dengan SDK fake', function () {
    HrKnowledgeBaseAgent::fake([[
        'answer' => 'ok',
        'confidence' => 'high',
    ]]);

    $client = new GeminiClient;

    expect($client->isHealthy())->toBeTrue();
});

test('generateContent handles empty context gracefully', function () {
    HrKnowledgeBaseAgent::fake([[
        'answer' => 'Maaf, tidak ada informasi yang relevan.',
        'confidence' => 'low',
    ]]);

    $client = new GeminiClient;

    $answer = $client->generateContent('Pertanyaan tanpa konteks', []);

    expect($answer)->toBeString()->not->toBeEmpty();
});

test('embed generates vectors for different input texts', function () {
    $client = new GeminiClient;

    $v1 = $client->embed('Cuti tahunan');
    $v2 = $client->embed('BPJS Kesehatan');

    expect($v1)->toHaveCount(768);
    expect($v2)->toHaveCount(768);
});
