<?php

use App\Notifications\AttendanceReminder;
use App\Notifications\ChronicLateWarning;
use App\Notifications\NewDeviceLogin;
use App\Notifications\PayrollPublished;
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
