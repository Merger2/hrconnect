<?php

use App\Livewire\Admin\MasterData\LeaveTypeManager;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

test('admin role can manage leave types while employees cannot', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();

    $adminRole = Role::query()->where('slug', 'admin')->firstOrFail();
    $admin->roles()->sync([$adminRole->id]);

    expect(Gate::forUser($admin)->allows('manageLeaveTypes'))->toBeTrue()
        ->and(Gate::forUser($employee)->allows('manageLeaveTypes'))->toBeFalse();
});

test('leave type manager can create custom leave type and prevents sick quota usage', function () {
    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin);

    Livewire::test(LeaveTypeManager::class)
        ->call('showCreating')
        ->set('name', 'Cuti Menikah')
        ->set('description', 'Cuti khusus untuk pernikahan karyawan.')
        ->set('category', LeaveType::CATEGORY_OTHER)
        ->set('counts_against_quota', false)
        ->set('requires_attachment', true)
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('leave_types', [
        'name' => 'Cuti Menikah',
        'category' => LeaveType::CATEGORY_OTHER,
        'counts_against_quota' => false,
        'requires_attachment' => true,
    ]);

    // Sick leave types never count against the annual quota — even when an
    // admin tries to toggle counts_against_quota on, the manager forces false.
    $sickLeave = LeaveType::create([
        'code' => 'sick_leave',
        'name' => 'Cuti Sakit',
        'category' => LeaveType::CATEGORY_SICK,
        'is_paid' => false,
        'deducts_from_quota' => false,
        'counts_against_quota' => false,
        'is_active' => true,
    ]);

    Livewire::test(LeaveTypeManager::class)
        ->call('edit', $sickLeave->id)
        ->set('counts_against_quota', true)
        ->call('update')
        ->assertHasNoErrors();

    expect($sickLeave->refresh()->counts_against_quota)->toBeFalse();
});
