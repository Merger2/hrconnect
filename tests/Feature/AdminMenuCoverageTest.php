<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

function adminMenuRouteNamesFromNavigation(): array
{
    $navigation = file_get_contents(resource_path('views/navigation-menu.blade.php'));

    preg_match_all("/route\\('(?<name>admin\\.[^']+)'/", $navigation ?: '', $matches);

    $routeNames = array_values(array_unique($matches['name'] ?? []));
    sort($routeNames);

    return $routeNames;
}

function adminMenuGroupIdsFromNavigation(): array
{
    $navigation = file_get_contents(resource_path('views/navigation-menu.blade.php'));

    preg_match_all("/'id'\\s*=>\\s*'(?<id>[a-z-]+)'/", $navigation ?: '', $matches);

    return array_values($matches['id'] ?? []);
}

test('admin navigation keeps six top level groups', function () {
    expect(adminMenuGroupIdsFromNavigation())->toBe([
        'overview',
        'attendance',
        'finance',
        'people',
        'operations',
        'system',
        'knowledge-base',
    ]);
});

test('admin navigation menu keeps every linked route registered', function () {
    $routeNames = adminMenuRouteNamesFromNavigation();

    expect($routeNames)
        ->not->toBeEmpty()
        ->toContain(
            'admin.dashboard',
            'admin.attendances',
            'admin.payrolls',
            'admin.employees',
            'admin.settings',
            'admin.operational-health',
            'admin.roles.permissions',
        );

    foreach ($routeNames as $routeName) {
        expect(Route::has($routeName))->toBeTrue("Missing admin menu route [{$routeName}].");
    }
});

test('superadmin can open every admin menu page', function () {

    $superadmin = User::factory()->admin(true)->create();

    foreach (adminMenuRouteNamesFromNavigation() as $routeName) {
        $response = $this
            ->actingAs($superadmin)
            ->followingRedirects()
            ->get(route($routeName));

        $response->assertOk("Admin menu route [{$routeName}] should render for superadmin.");
    }
});

test('admin navigation menu has broad route coverage', function () {
    expect(count(adminMenuRouteNamesFromNavigation()))->toBeGreaterThanOrEqual(37);
});
