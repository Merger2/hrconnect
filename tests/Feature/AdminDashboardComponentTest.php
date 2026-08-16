<?php

use App\Contracts\AuditServiceInterface;
use App\Livewire\Admin\DashboardComponent;
use App\Mail\CheckoutReminderMail;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use App\Support\AdminDashboardQueryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function fakeDashboardAuditRecorder(): object
{
    return new class implements AuditServiceInterface
    {
        public array $records = [];

        public function record(string $action, ?string $description = null)
        {
            $this->records[] = compact('action', 'description');

            return null;
        }

        public function getTrail(array $filters = []): array
        {
            return $this->records;
        }
    };
}

/**
 * Create a user + employee pair (attendances.employee_id is the FK).
 */
function createDashboardEmployee(array $userAttributes = []): array
{
    $user = User::factory()->create($userAttributes);
    $employee = Employee::factory()->for($user)->create();

    return [$user, $employee];
}

test('admin dashboard renders recent activity and overdue checkout data', function () {
    $this->travelTo(Carbon::parse('2026-05-14 12:00:00'));

    $admin = User::factory()->admin(true)->create();
    [$employee, $employeeRecord] = createDashboardEmployee(['name' => 'Dashboard Employee']);
    $shiftEnd = now()->subHour();
    $shiftStart = $shiftEnd->copy()->subHours(8);
    $shift = Shift::factory()->create([
        'start_time' => $shiftStart->format('H:i:s'),
        'end_time' => $shiftEnd->format('H:i:s'),
    ]);

    Attendance::create([
        'employee_id' => $employeeRecord->id,
        'date' => now()->toDateString(),
        'clock_in' => $shiftStart->copy()->addMinutes(5),
        'clock_out' => null,
        'shift_id' => $shift->id,
        'status' => 'present',
    ]);

    ActivityLog::create([
        'user_id' => $employee->id,
        'action' => 'Login Successful',
        'description' => 'User logged in.',
        'ip_address' => '127.0.0.1',
    ]);

    $this->actingAs($admin);

    Livewire::test(DashboardComponent::class)
        ->assertSee('Dashboard Employee')
        ->assertViewHas('overdueUsers', fn ($users) => $users->contains(fn ($attendance) => $attendance->employee_id === $employeeRecord->id));
});

test('dashboard reminder action queues checkout reminder email and audits it', function () {
    Mail::fake();

    $audit = fakeDashboardAuditRecorder();
    app()->instance(AuditServiceInterface::class, $audit);

    $admin = User::factory()->admin(true)->create();
    [$employee, $employeeRecord] = createDashboardEmployee([
        'name' => 'Reminder Employee',
        'email' => 'reminder@example.com',
    ]);

    $attendance = Attendance::create([
        'employee_id' => $employeeRecord->id,
        'date' => now()->toDateString(),
        'clock_in' => now()->subHours(9),
        'clock_out' => null,
        'status' => 'present',
    ]);

    $this->actingAs($admin);

    Livewire::test(DashboardComponent::class)
        ->call('notifyUser', $attendance->id);

    Mail::assertQueued(CheckoutReminderMail::class, function (CheckoutReminderMail $mail) use ($employee) {
        return $mail->user->is($employee);
    });

    expect($audit->records)->toHaveCount(1)
        ->and($audit->records[0]['action'])->toBe('Notification Sent')
        ->and($audit->records[0]['description'])->toBe('Sent checkout reminder to Reminder Employee');
});

test('admin dashboard movement chart separates excused and sick leave', function () {
    $admin = User::factory()->admin(true)->create();
    [, $excusedRecord] = createDashboardEmployee();
    [, $sickRecord] = createDashboardEmployee();
    $date = now()->startOfDay();

    Attendance::create([
        'employee_id' => $excusedRecord->id,
        'date' => $date->toDateString(),
        'status' => 'excused',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);

    Attendance::create([
        'employee_id' => $sickRecord->id,
        'date' => $date->toDateString(),
        'status' => 'sick',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);

    $chartData = app(AdminDashboardQueryService::class)->chartData($admin, $date, 'week_1');

    expect($chartData['excused'][array_key_last($chartData['excused'])])->toBe(1)
        ->and($chartData['sick'][array_key_last($chartData['sick'])])->toBe(1)
        ->and($chartData['absent'][array_key_last($chartData['absent'])])->toBeGreaterThanOrEqual(0);
});

test('admin dashboard upcoming leaves excludes dates before today', function () {
    $this->travelTo(Carbon::parse('2026-04-15 09:00:00'));

    $admin = User::factory()->admin(true)->create();
    [$pastUser, $pastRecord] = createDashboardEmployee(['name' => 'Past Leave Employee']);
    [$todayUser, $todayRecord] = createDashboardEmployee(['name' => 'Today Leave Employee']);

    Attendance::create([
        'employee_id' => $pastRecord->id,
        'date' => now()->subDay()->toDateString(),
        'status' => 'excused',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);

    Attendance::create([
        'employee_id' => $todayRecord->id,
        'date' => now()->toDateString(),
        'status' => 'excused',
        'approval_status' => Attendance::STATUS_APPROVED,
    ]);

    $dashboard = app(AdminDashboardQueryService::class)->build($admin, now()->startOfDay());
    $titles = collect($dashboard['calendarLeaves'])->pluck('title')->all();

    expect($titles)
        ->toContain('Today Leave Employee')
        ->not->toContain('Past Leave Employee');

    // Past-day leaves are excluded because calendarLeaves starts from today.
    expect($titles)->not->toContain($pastUser->name);
    expect($titles)->toContain($todayUser->name);
});
