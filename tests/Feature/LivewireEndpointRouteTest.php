<?php

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

test('the configured Livewire update endpoint retains the version four hash path', function () {
    $route = Route::getRoutes()->getByName('livewire.update');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toMatch('/^livewire-[a-f0-9]+\/update$/')
        ->and(Livewire::getUpdateUri())->toBe('/'.$route->uri());
});
