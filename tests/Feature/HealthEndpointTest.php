<?php

use Illuminate\Support\Facades\Cache;

test('public health endpoint returns non-sensitive readiness checks', function () {
    Cache::put('health:scheduler_heartbeat_at', now()->toIso8601String());

    $this->get('/up')->assertOk();

    $this->get(route('health'))
        ->assertOk()
        ->assertJsonStructure([
            'status',
            'version',
            'checks' => [
                'database',
                'cache',
                'storage',
                'scheduler_seen',
            ],
        ])
        ->assertDontSee('license')
        ->assertDontSee(base_path());
});
