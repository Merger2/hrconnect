<?php

use App\Enums\PayrollStatus;
use App\Livewire\Admin\PayrollManager;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use App\Notifications\PayrollPaid;
use App\Notifications\PayrollPublished;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();
});

test('approving payroll dispatches PayrollPublished notification to employee user', function () {
    $admin = User::factory()->admin(true)->create();
    $admin->assignRole('super-admin');
    $employeeUser = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $employeeUser->id]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5_000_000,
        'total_allowance' => 500_000,
        'gross_salary' => 5_500_000,
        'total_deduction' => 500_000,
        'net_salary' => 5_000_000,
        'status' => PayrollStatus::VERIFIED,
    ]);

    Livewire::actingAs($admin)
        ->test(PayrollManager::class)
        ->call('approve', $payroll->id)
        ->assertHasNoErrors();

    expect($payroll->fresh()->status)->toBe(PayrollStatus::APPROVED);

    Notification::assertSentTo($employeeUser, PayrollPublished::class, function (PayrollPublished $notification) use ($payroll) {
        return $notification->payroll->is($payroll);
    });
});

test('paying payroll dispatches PayrollPaid notification to employee user', function () {
    $admin = User::factory()->admin(true)->create();
    $admin->assignRole('super-admin');
    $employeeUser = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $employeeUser->id]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5_000_000,
        'total_allowance' => 500_000,
        'gross_salary' => 5_500_000,
        'total_deduction' => 500_000,
        'net_salary' => 5_000_000,
        'status' => PayrollStatus::APPROVED,
    ]);

    Livewire::actingAs($admin)
        ->test(PayrollManager::class)
        ->call('markPaid', $payroll->id)
        ->assertHasNoErrors();

    expect($payroll->fresh()->status)->toBe(PayrollStatus::PAID);

    Notification::assertSentTo($employeeUser, PayrollPaid::class, function (PayrollPaid $notification) use ($payroll) {
        return $notification->payroll->is($payroll);
    });
});
