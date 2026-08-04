<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {});

test('superadmin can open every concrete admin get page without server errors', function () {
    $superadmin = User::factory()->admin(true)->create();

    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'admin.'))
        ->filter(fn ($route) => in_array('GET', $route->methods(), true))
        ->reject(fn ($route) => str_contains($route->uri(), '{'))
        ->reject(fn ($route) => str_ends_with((string) $route->getName(), '.export'))
        ->sortBy(fn ($route) => $route->getName());

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        $response = $this
            ->actingAs($superadmin)
            ->get(route($route->getName()));

        $this->assertContains(
            $response->getStatusCode(),
            [200, 301, 302, 303],
            "Admin route [{$route->getName()}] returned {$response->getStatusCode()}."
        );
    }
});
