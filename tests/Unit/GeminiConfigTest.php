<?php

use Tests\TestCase;

uses(TestCase::class);

test('Gemini service and AI SDK configs resolve the same API key', function () {
    expect(config('services.gemini.api_key'))
        ->toBe(config('ai.providers.gemini.key'));
});
