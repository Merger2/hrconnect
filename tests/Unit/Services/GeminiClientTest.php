<?php

use App\Exceptions\BusinessRuleException;
use App\Services\GeminiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * GeminiClient unit tests — fokus mock mode + retry logic.
 *
 * Real API calls tidak di-test karena butuh API key. Mock mode di-cover
 * untuk validasi shape response + deterministic vector.
 */
test('mock mode return 768D vector', function () {
    $client = new GeminiClient(mockMode: true);

    $vector = $client->embed('Test pertanyaan');

    expect($vector)->toBeArray();
    expect(count($vector))->toBe(768);

    foreach ($vector as $v) {
        expect($v)->toBeFloat();
        expect(abs($v))->toBeLessThanOrEqual(1.5);
    }
});

test('mock mode return deterministic vector untuk teks apapun', function () {
    $client = new GeminiClient(mockMode: true);

    $vector1 = $client->embed('apa pun');
    $vector2 = $client->embed('teks lain');

    // Mock mode: vector identik karena hash-based deterministic dari sin()
    expect($vector1)->toBe($vector2);
});

test('mock mode generateContent return canned response saat context kosong', function () {
    $client = new GeminiClient(mockMode: true);

    $answer = $client->generateContent('Apa kabar?', []);

    expect($answer)->toContain('Maaf');
    expect($answer)->toContain('mock mode');
});

test('mock mode generateContent return response dengan context', function () {
    $client = new GeminiClient(mockMode: true);

    $answer = $client->generateContent('Berapa cuti tahunan?', [
        ['content' => 'Cuti tahunan karyawan adalah 12 hari', 'source' => 'Employee Handbook'],
    ]);

    expect($answer)->toContain('Berdasarkan');
    expect($answer)->toContain('Berapa cuti tahunan?');
    expect($answer)->toContain('mock mode');
});

test('embed reject teks kosong', function () {
    $client = new GeminiClient(mockMode: false);

    expect(fn () => $client->embed(''))
        ->toThrow(BusinessRuleException::class, 'harus 1-30000 karakter');
});

test('embed reject teks terlalu panjang (> 30000 char)', function () {
    $client = new GeminiClient(mockMode: false);

    $longText = str_repeat('a', 30_001);

    expect(fn () => $client->embed($longText))
        ->toThrow(BusinessRuleException::class, 'harus 1-30000 karakter');
});

test('isHealthy return true di mock mode', function () {
    $client = new GeminiClient(mockMode: true);

    expect($client->isHealthy())->toBeTrue();
});
