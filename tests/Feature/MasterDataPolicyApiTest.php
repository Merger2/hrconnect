<?php

use App\Models\Branch;
use App\Models\Division;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Policies\BranchPolicy;
use App\Policies\DivisionPolicy;
use App\Policies\PositionPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Regression 403 master data API (temuan K6 run 2026-09-06).
 *
 * Sebelum fix: /api/v1/branches|divisions|positions 403 untuk SEMUA
 * non-superadmin — controller authorize('viewAny', Model::class) tanpa
 * Policy, sementara route gate view_branches/view_divisions/view_positions
 * sudah benar dan role admin memang memilikinya.
 *
 * Sesudah fix: BranchPolicy/DivisionPolicy/PositionPolicy menyamakan layer
 * policy dengan gate route. Kasus nyata yang dibungkus test di sini:
 * user group=user dengan role yang punya permission_keys master data
 * (profil akun K6: k6.hr@hrconnect.test) harus 200.
 */
beforeEach(function () {
    // Role dengan permission master data — mirror permission_keys role admin
    // di RoleAndPermissionSeeder, tanpa tergantung seeder penuh.
    $this->masterDataRole = Role::create([
        'name' => 'Master Data Viewer_'.uniqid(),
        'slug' => 'master_data_viewer_'.uniqid(),
        'description' => 'Bisa lihat master data branch/division/position.',
        'permission_keys' => ['view_branches', 'view_divisions', 'view_positions'],
    ]);

    $this->viewer = User::factory()->create(['group' => 'user']);
    $this->viewer->roles()->sync([$this->masterDataRole->id]);
});

test('policies untuk master data terdaftar di gate', function () {
    expect(Gate::getPolicyFor(Branch::class))->toBeInstanceOf(BranchPolicy::class)
        ->and(Gate::getPolicyFor(Division::class))->toBeInstanceOf(DivisionPolicy::class)
        ->and(Gate::getPolicyFor(Position::class))->toBeInstanceOf(PositionPolicy::class);
});

test('user dengan permission master data lolos viewAny di layer policy', function () {
    expect($this->viewer->can('viewAny', Branch::class))->toBeTrue()
        ->and($this->viewer->can('viewAny', Division::class))->toBeTrue()
        ->and($this->viewer->can('viewAny', Position::class))->toBeTrue();
});

test('user tanpa permission master data ditolak di layer policy', function () {
    $plainUser = User::factory()->create(['group' => 'user']);

    expect($plainUser->can('viewAny', Branch::class))->toBeFalse()
        ->and($plainUser->can('viewAny', Division::class))->toBeFalse()
        ->and($plainUser->can('viewAny', Position::class))->toBeFalse();
});

test('GET /api/v1/branches 200 untuk role dengan view_branches (kasus K6)', function () {
    Branch::factory()->count(2)->create();

    $response = $this->actingAs($this->viewer, 'sanctum')
        ->getJson('/api/v1/branches');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

test('GET /api/v1/divisions 200 untuk role dengan view_divisions', function () {
    Division::factory()->count(2)->create();

    $this->actingAs($this->viewer, 'sanctum')
        ->getJson('/api/v1/divisions')
        ->assertOk();
});

test('GET /api/v1/positions 200 untuk role dengan view_positions', function () {
    Position::factory()->count(2)->create();

    $this->actingAs($this->viewer, 'sanctum')
        ->getJson('/api/v1/positions')
        ->assertOk();
});

test('GET /api/v1/branches 403 untuk user tanpa permission', function () {
    $plainUser = User::factory()->create(['group' => 'user']);

    $this->actingAs($plainUser, 'sanctum')
        ->getJson('/api/v1/branches')
        ->assertForbidden();
});

test('superadmin tetap lolos tanpa permission eksplisit (bypass before)', function () {
    $superadmin = User::factory()->create(['group' => 'superadmin']);

    Branch::factory()->create();

    $this->actingAs($superadmin, 'sanctum')
        ->getJson('/api/v1/branches')
        ->assertOk();
});

test('show endpoint tetap terjaga oleh policy view', function () {
    $branch = Branch::factory()->create();

    $this->actingAs($this->viewer, 'sanctum')
        ->getJson("/api/v1/branches/{$branch->id}")
        ->assertOk();

    $plainUser = User::factory()->create(['group' => 'user']);

    $this->actingAs($plainUser, 'sanctum')
        ->getJson("/api/v1/branches/{$branch->id}")
        ->assertForbidden();
});
