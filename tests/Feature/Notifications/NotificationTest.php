<?php

use App\Notifications\ApprovalOverdue;
use App\Notifications\AttendanceReminder;
use App\Notifications\ChronicLateWarning;
use App\Notifications\LeaveApproved;
use App\Notifications\LeaveRejected;
use App\Notifications\LeaveRequestSubmitted;
use App\Notifications\NewDeviceLogin;
use App\Notifications\PayrollPublished;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;

it('sends AttendanceReminder via mail and database', function () {
    $notification = new AttendanceReminder;

    $notifiable = new class
    {
        use Notifiable;

        public $email = 'test@example.com';
    };

    expect($notification->via($notifiable))->toBe(['mail', 'database']);

    $mail = $notification->toMail($notifiable);
    expect($mail)->toBeInstanceOf(MailMessage::class);
    expect($mail->subject)->toBe('Reminder Clock-In');

    $array = $notification->toArray($notifiable);
    expect($array)->toHaveKey('message');
    expect($array['message'])->toContain('belum clock-in');
});

it('sends PayrollPublished via mail and database', function () {
    $payroll = Mockery::mock('App\Models\Payroll');
    $payroll->shouldReceive('getAttribute')->with('period')->andReturn('2026-06');
    $payroll->shouldReceive('getAttribute')->with('net_salary')->andReturn(5000000);
    $employee = Mockery::mock('App\Models\Employee');
    $employee->shouldReceive('getAttribute')->with('full_name')->andReturn('Test User');
    $payroll->shouldReceive('getAttribute')->with('employee')->andReturn($employee);
    $payroll->shouldReceive('getAttribute')->with('id')->andReturn(1);

    $notification = new PayrollPublished($payroll);

    $notifiable = new class
    {
        use Notifiable;

        public $email = 'test@example.com';
    };

    expect($notification->via($notifiable))->toBe(['mail', 'database']);

    $mail = $notification->toMail($notifiable);
    expect($mail)->toBeInstanceOf(MailMessage::class);
    expect($mail->subject)->toContain('Slip Gaji');

    $array = $notification->toArray($notifiable);
    expect($array)->toHaveKey('payroll_id');
    expect($array)->toHaveKey('period');
    expect($array)->toHaveKey('net_salary');
    expect($array['message'])->toContain('diterbitkan');
});

it('sends LeaveRequestSubmitted via mail and database', function () {
    $leaveType = Mockery::mock('App\Models\LeaveType');
    $leaveType->shouldReceive('getAttribute')->with('name')->andReturn('Cuti Tahunan');

    $employee = Mockery::mock('App\Models\Employee');
    $employee->shouldReceive('getAttribute')->with('full_name')->andReturn('Test User');

    $leave = Mockery::mock('App\Models\Leave');
    $leave->shouldReceive('getAttribute')->with('id')->andReturn(1);
    $leave->shouldReceive('getAttribute')->with('leaveType')->andReturn($leaveType);
    $leave->shouldReceive('getAttribute')->with('employee')->andReturn($employee);
    $leave->shouldReceive('getAttribute')->with('total_days')->andReturn(2);
    $startDate = CarbonImmutable::parse('2026-06-01');
    $endDate = CarbonImmutable::parse('2026-06-02');
    $leave->shouldReceive('getAttribute')->with('start_date')->andReturn($startDate);
    $leave->shouldReceive('getAttribute')->with('end_date')->andReturn($endDate);

    $notification = new LeaveRequestSubmitted($leave);

    $notifiable = new class
    {
        use Notifiable;

        public $email = 'test@example.com';
    };

    expect($notification->via($notifiable))->toBe(['mail', 'database']);
    expect($notification->toMail($notifiable)->subject)->toContain('Pengajuan Cuti Baru');

    $array = $notification->toArray($notifiable);
    expect($array)->toHaveKeys(['leave_id', 'employee_name', 'leave_type', 'start_date', 'end_date', 'message']);
    expect($array['message'])->toContain('Pengajuan cuti baru');
});

it('sends LeaveApproved via mail and database', function () {
    $leaveType = Mockery::mock('App\Models\LeaveType');
    $leaveType->shouldReceive('getAttribute')->with('name')->andReturn('Cuti Tahunan');

    $leave = Mockery::mock('App\Models\Leave');
    $leave->shouldReceive('getAttribute')->with('id')->andReturn(1);
    $leave->shouldReceive('getAttribute')->with('leaveType')->andReturn($leaveType);
    $leave->shouldReceive('getAttribute')->with('total_days')->andReturn(2);
    $startDate = CarbonImmutable::parse('2026-06-01');
    $endDate = CarbonImmutable::parse('2026-06-02');
    $leave->shouldReceive('getAttribute')->with('start_date')->andReturn($startDate);
    $leave->shouldReceive('getAttribute')->with('end_date')->andReturn($endDate);

    $notification = new LeaveApproved($leave);

    $notifiable = new class
    {
        use Notifiable;

        public $email = 'test@example.com';
    };

    expect($notification->via($notifiable))->toBe(['mail', 'database']);
    expect($notification->toMail($notifiable)->subject)->toBe('Cuti Anda Telah Disetujui');

    $array = $notification->toArray($notifiable);
    expect($array['message'])->toBe('Cuti Anda telah disetujui.');
});

it('sends LeaveRejected via mail and database with rejection reason', function () {
    $leaveType = Mockery::mock('App\Models\LeaveType');
    $leaveType->shouldReceive('getAttribute')->with('name')->andReturn('Cuti Tahunan');

    $leave = Mockery::mock('App\Models\Leave');
    $leave->shouldReceive('getAttribute')->with('id')->andReturn(1);
    $leave->shouldReceive('getAttribute')->with('leaveType')->andReturn($leaveType);
    $leave->shouldReceive('getAttribute')->with('total_days')->andReturn(2);
    $leave->shouldReceive('getAttribute')->with('rejection_reason')->andReturn('Kuota penuh');
    $startDate = CarbonImmutable::parse('2026-06-01');
    $endDate = CarbonImmutable::parse('2026-06-02');
    $leave->shouldReceive('getAttribute')->with('start_date')->andReturn($startDate);
    $leave->shouldReceive('getAttribute')->with('end_date')->andReturn($endDate);

    $notification = new LeaveRejected($leave);

    $notifiable = new class
    {
        use Notifiable;

        public $email = 'test@example.com';
    };

    expect($notification->via($notifiable))->toBe(['mail', 'database']);
    expect($notification->toMail($notifiable)->subject)->toBe('Cuti Anda Ditolak');

    $array = $notification->toArray($notifiable);
    expect($array['rejection_reason'])->toBe('Kuota penuh');
});

it('sends ChronicLateWarning via mail and database', function () {
    $employee = Mockery::mock('App\Models\Employee');
    $employee->shouldReceive('getAttribute')->with('id')->andReturn(1);
    $employee->shouldReceive('getAttribute')->with('full_name')->andReturn('Test User');

    $notification = new ChronicLateWarning($employee, 5);

    $notifiable = new class
    {
        use Notifiable;

        public $email = 'test@example.com';
    };

    expect($notification->via($notifiable))->toBe(['mail', 'database']);
    expect($notification->toMail($notifiable)->subject)->toBe('Peringatan Keterlambatan Kronis');

    $array = $notification->toArray($notifiable);
    expect($array['late_count'])->toBe(5);
});

it('sends NewDeviceLogin via mail and database', function () {
    $device = Mockery::mock('App\Models\Device');
    $device->shouldReceive('getAttribute')->with('id')->andReturn(1);
    $device->shouldReceive('getAttribute')->with('device_name')->andReturn('Chrome on Linux');
    $device->shouldReceive('getAttribute')->with('device_uuid')->andReturn('abc-123');
    $device->shouldReceive('getAttribute')->with('browser')->andReturn('Chrome');
    $device->shouldReceive('getAttribute')->with('os')->andReturn('Linux');

    $notification = new NewDeviceLogin($device);

    $notifiable = new class
    {
        use Notifiable;

        public $email = 'test@example.com';
    };

    expect($notification->via($notifiable))->toBe(['mail', 'database']);
    expect($notification->toMail($notifiable)->subject)->toBe('Login dari Perangkat Baru');

    $array = $notification->toArray($notifiable);
    expect($array['device_name'])->toBe('Chrome on Linux');
});

it('sends ApprovalOverdue via database only', function () {
    $approver = Mockery::mock('App\Models\Employee');
    $approver->shouldReceive('getAttribute')->with('id')->andReturn(1);
    $approver->shouldReceive('getAttribute')->with('full_name')->andReturn('Manager');
    $approver->shouldReceive('offsetExists')->andReturn(true);
    $approver->shouldReceive('offsetGet')->andReturn(null);

    $approval = Mockery::mock('App\Models\Approval');
    $approval->shouldReceive('getAttribute')->with('id')->andReturn(1);
    $approval->shouldReceive('getAttribute')->with('approvable_type')->andReturn('Leave');
    $approval->shouldReceive('getAttribute')->with('approvable_id')->andReturn(1);
    $approval->shouldReceive('getAttribute')->with('approver')->andReturn($approver);
    $approval->shouldReceive('getAttribute')->with('approvable')->andReturn(null);
    $approval->shouldReceive('offsetExists')->andReturn(true);
    $approval->shouldReceive('offsetGet')->andReturn(null);

    $notification = new ApprovalOverdue($approval);

    $notifiable = new class
    {
        use Notifiable;

        public $email = 'test@example.com';
    };

    expect($notification->via($notifiable))->toBe(['database']);

    $array = $notification->toArray($notifiable);
    expect($array)->toHaveKeys(['approval_id', 'approvable_type', 'approvable_id', 'approver_name', 'message']);
    expect($array['approver_name'])->toBe('Manager');
});
