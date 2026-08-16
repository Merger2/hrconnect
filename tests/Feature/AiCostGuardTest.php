<?php

use App\Enums\KnowledgeBaseCategory;
use App\Enums\KnowledgeBaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\KnowledgeBase;
use App\Services\KnowledgeBase\KnowledgeBaseService;
use App\Services\Security\EmbeddingService;
use App\Support\AiCostGuard;

beforeEach(function () {
    app(AiCostGuard::class)->reset();
    config(['services.gemini.daily_token_budget' => 1000]);
});

test('cost guard tracks daily token usage and respects budget', function () {
    $guard = app(AiCostGuard::class);

    expect($guard->remaining())->toBe(1000)
        ->and($guard->canSpend(600))->toBeTrue();

    $guard->record(600);

    expect($guard->spent())->toBe(600)
        ->and($guard->remaining())->toBe(400)
        ->and($guard->canSpend(500))->toBeFalse()
        ->and($guard->canSpend(400))->toBeTrue();
});

test('cost guard estimates tokens from character count', function () {
    $guard = app(AiCostGuard::class);

    expect($guard->estimateTokens(str_repeat('a', 400)))->toBe(100)
        ->and($guard->estimateTokens(''))->toBe(0);
});

test('cost guard is disabled when budget is zero', function () {
    config(['services.gemini.daily_token_budget' => 0]);

    $guard = app(AiCostGuard::class);

    expect($guard->isEnabled())->toBeFalse()
        ->and($guard->canSpend(PHP_INT_MAX / 2))->toBeTrue();

    $guard->record(500);

    expect($guard->spent())->toBe(0);
});

test('chat falls back to keyword search when daily AI budget is exceeded', function () {
    config(['services.gemini.daily_token_budget' => 10]);
    app(AiCostGuard::class)->record(10);

    KnowledgeBase::create([
        'knowledgeable_type' => 'App\Models\Company',
        'knowledgeable_id' => 1,
        'title' => 'Jam Kerja',
        'content' => 'Jam kerja dimulai pukul delapan pagi dan berakhir pukul lima sore setiap hari kerja.',
        'category' => KnowledgeBaseCategory::GENERAL,
        'status' => KnowledgeBaseStatus::READY,
    ]);

    $result = app(KnowledgeBaseService::class)->chat('jam kerja');

    expect($result['fallback'])->toBeTrue()
        ->and($result['model'])->toBe('pg_trgm')
        ->and($result['sources'])->not->toBeEmpty()
        ->and($result['answer'])->toContain('Kuota penggunaan AI harian untuk hari ini telah tercapai');
});

test('chatStream falls back to keyword snippets when daily AI budget is exceeded', function () {
    config(['services.gemini.daily_token_budget' => 10]);
    app(AiCostGuard::class)->record(10);

    KnowledgeBase::create([
        'knowledgeable_type' => 'App\Models\Company',
        'knowledgeable_id' => 1,
        'title' => 'Jam Kerja',
        'content' => 'Jam kerja dimulai pukul delapan pagi dan berakhir pukul lima sore setiap hari kerja.',
        'category' => KnowledgeBaseCategory::GENERAL,
        'status' => KnowledgeBaseStatus::READY,
    ]);

    $events = iterator_to_array(app(KnowledgeBaseService::class)->chatStream('jam kerja'));

    $text = collect($events)->pluck('text')->filter()->implode(' ');
    $last = $events[array_key_last($events)];

    expect($text)->toContain('Kuota penggunaan AI harian untuk hari ini telah tercapai')
        ->and($last['fallback'] ?? false)->toBeTrue();
});

test('embedding refuses to call provider when daily AI budget is exceeded', function () {
    config(['services.gemini.daily_token_budget' => 10]);
    app(AiCostGuard::class)->record(10);

    expect(fn () => app(EmbeddingService::class)->embed(str_repeat('x', 200)))
        ->toThrow(BusinessRuleException::class, 'Kuota penggunaan AI harian telah tercapai');
});
