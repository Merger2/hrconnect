<?php

use App\Livewire\Admin\EmployeeComponent;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;

// Regression test (2026-08-12): halaman /admin/employees melempar 500
// UrlGenerationException ("Missing required parameter [employee]") karena
// tombol Edit memanggil route('admin.employees.edit', $user->employee) dengan
// $user->employee null (user tanpa record employee: superadmin, user test,
// akun e2e). Fix: tombol Edit hanya dirender jika $user->employee ada.

test('employees index renders OK when a listed user has no employee record', function () {
    $superadmin = User::factory()->admin(true)->create();
    // User TANPA employee record — pemicu bug UrlGenerationException
    User::factory()->create(['name' => 'No Employee User']);

    $this->actingAs($superadmin)
        ->get(route('admin.employees'))
        ->assertOk()
        ->assertSee('No Employee User');
});

test('employees index renders OK for superadmin itself (no employee record)', function () {
    $superadmin = User::factory()->admin(true)->create(['name' => 'Root Admin']);

    $this->actingAs($superadmin)
        ->get(route('admin.employees'))
        ->assertOk()
        ->assertSee('Root Admin');
});

test('employees index hides edit button for users without employee record', function () {
    $superadmin = User::factory()->admin(true)->create();
    User::factory()->create(['name' => 'Ghost User']);
    $withEmployee = User::factory()->create(['name' => 'Real Employee']);
    Employee::factory()->create(['user_id' => $withEmployee->id]);

    $html = $this->actingAs($superadmin)
        ->get(route('admin.employees'))
        ->assertOk()
        ->getContent();

    // Semua link edit yang dirender harus menunjuk ke id EMPLOYEE yang valid.
    // (Tidak membandingkan string route dgn user id — sequence users/employees
    // bisa collide di env fresh, membuat assertion negatif spurious.)
    preg_match_all('#/admin/employees/\d+/edit#', $html, $matches);
    $editIds = array_unique($matches[0]);

    expect($editIds)->not->toBeEmpty();
    foreach ($editIds as $href) {
        expect($href)->toContain('/admin/employees/'.$withEmployee->employee->id.'/edit');
    }
});

test('employees component Livewire render does not throw for user without employee', function () {
    $superadmin = User::factory()->admin(true)->create();
    User::factory()->create(['name' => 'Ghost User']);

    Livewire::actingAs($superadmin)
        ->test(EmployeeComponent::class)
        ->assertOk();
});
