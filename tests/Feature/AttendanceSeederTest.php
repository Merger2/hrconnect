<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shift;
use Database\Seeders\AttendanceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it seeds attendance records without development-only dependencies', function () {
    $employee = Employee::factory()->create();
    Shift::factory()->create();

    app(AttendanceSeeder::class)->run();

    expect(Attendance::query()->where('employee_id', $employee->id)->count())
        ->toBeGreaterThan(0);
});
