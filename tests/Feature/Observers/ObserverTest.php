<?php

use App\Enums\PayrollStatus;
use App\Jobs\GeneratePayslipPdfJob;
use App\Models\Attendance;
use App\Models\BpjsConfig;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Shift;
use App\Models\TaxConfig;
use App\Observers\AttendanceObserver;
use App\Observers\BpjsConfigObserver;
use App\Observers\EmployeeObserver;
use App\Observers\HolidayObserver;
use App\Observers\LeaveObserver;
use App\Observers\PayrollObserver;
use App\Observers\TaxConfigObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class)->group('observers');

describe('TaxConfigObserver', function () {
    it('invalidates cache on saved', function () {
        Cache::shouldReceive('forget')->with('tax_configs')->once();
        (new TaxConfigObserver)->saved(TaxConfig::factory()->make());
    });

    it('invalidates cache on deleted', function () {
        Cache::shouldReceive('forget')->with('tax_configs')->once();
        (new TaxConfigObserver)->deleted(TaxConfig::factory()->make());
    });
});

describe('BpjsConfigObserver', function () {
    it('invalidates cache on saved', function () {
        Cache::shouldReceive('forget')->with('bpjs_configs')->once();
        (new BpjsConfigObserver)->saved(BpjsConfig::factory()->make());
    });

    it('invalidates cache on deleted', function () {
        Cache::shouldReceive('forget')->with('bpjs_configs')->once();
        (new BpjsConfigObserver)->deleted(BpjsConfig::factory()->make());
    });
});

describe('HolidayObserver', function () {
    it('invalidates cache on saved', function () {
        $holiday = Holiday::factory()->make(['date' => '2026-06-17']);

        Cache::shouldReceive('forget')->with('holidays:2026')->once();

        (new HolidayObserver)->saved($holiday);
    });

    it('invalidates cache on deleted', function () {
        $holiday = Holiday::factory()->make(['date' => '2026-06-17']);

        Cache::shouldReceive('forget')->with('holidays:2026')->once();

        (new HolidayObserver)->deleted($holiday);
    });
});

describe('EmployeeObserver', function () {
    it('assigns default shift when creating employee without shift_id', function () {
        Shift::factory()->create(['is_active' => true, 'name' => 'Office Hour 1']);

        $employee = new Employee;
        $employee->full_name = 'Test';

        (new EmployeeObserver)->creating($employee);

        expect($employee->shift_id)->not->toBeNull();
    });

    it('skips shift assignment when shift_id already set', function () {
        $employee = new Employee;
        $employee->full_name = 'Test';
        $employee->shift_id = 999;

        (new EmployeeObserver)->creating($employee);

        expect($employee->shift_id)->toBe(999);
    });

    it('uses first active shift when no Office Hour shift exists', function () {
        Shift::factory()->create(['is_active' => true, 'name' => 'Night Shift']);

        $employee = new Employee;
        $employee->full_name = 'Test';

        (new EmployeeObserver)->creating($employee);

        expect($employee->shift_id)->not->toBeNull();
    });
});

describe('AttendanceObserver', function () {
    it('invalidates today and monthly cache on saved', function () {
        $attendance = Attendance::factory()->make([
            'date' => '2026-06-17',
            'employee_id' => 1,
        ]);

        Cache::shouldReceive('forget')->with('attendance:today:1')->once();
        Cache::shouldReceive('forget')->with('attendance:monthly:1:2026-06')->once();

        (new AttendanceObserver)->saved($attendance);
    });

    it('invalidates today and monthly cache on deleted', function () {
        $attendance = Attendance::factory()->make([
            'date' => '2026-06-17',
            'employee_id' => 1,
        ]);

        Cache::shouldReceive('forget')->with('attendance:today:1')->once();
        Cache::shouldReceive('forget')->with('attendance:monthly:1:2026-06')->once();

        (new AttendanceObserver)->deleted($attendance);
    });
});

describe('LeaveObserver', function () {
    it('invalidates leave caches on saved', function () {
        $leave = Leave::factory()->make([
            'start_date' => '2026-06-17',
            'employee_id' => 1,
        ]);

        Cache::shouldReceive('forget')->with('leave:history:1')->once();
        Cache::shouldReceive('forget')->with('leave:pending:1')->once();
        Cache::shouldReceive('forget')->with('leave:summary:1:2026')->once();

        (new LeaveObserver)->saved($leave);
    });

    it('invalidates leave caches on deleted', function () {
        $leave = Leave::factory()->make([
            'start_date' => '2026-06-17',
            'employee_id' => 1,
        ]);

        Cache::shouldReceive('forget')->with('leave:history:1')->once();
        Cache::shouldReceive('forget')->with('leave:pending:1')->once();
        Cache::shouldReceive('forget')->with('leave:summary:1:2026')->once();

        (new LeaveObserver)->deleted($leave);
    });
});

describe('PayrollObserver', function () {
    it('dispatches GeneratePayslipPdfJob when status changes to PUBLISHED', function () {
        Queue::fake();

        $payroll = Mockery::mock(Payroll::class)->makePartial();
        $payroll->shouldReceive('wasChanged')->with('status')->andReturnTrue();
        $payroll->status = PayrollStatus::PUBLISHED;

        (new PayrollObserver)->updated($payroll);

        Queue::assertPushed(GeneratePayslipPdfJob::class);
    });

    it('does not dispatch when status is not PUBLISHED', function () {
        Queue::fake();

        $payroll = Mockery::mock(Payroll::class)->makePartial();
        $payroll->shouldReceive('wasChanged')->with('status')->andReturnTrue();
        $payroll->status = PayrollStatus::PAID;

        (new PayrollObserver)->updated($payroll);

        Queue::assertNotPushed(GeneratePayslipPdfJob::class);
    });

    it('does not dispatch when wasChanged is false', function () {
        Queue::fake();

        $payroll = Mockery::mock(Payroll::class)->makePartial();
        $payroll->shouldReceive('wasChanged')->with('status')->andReturnFalse();
        $payroll->status = PayrollStatus::PUBLISHED;

        (new PayrollObserver)->updated($payroll);

        Queue::assertNotPushed(GeneratePayslipPdfJob::class);
    });
});
