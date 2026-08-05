<?php

use App\Livewire\User\AttendanceHistoryComponent;
use App\Livewire\User\NotificationsPage;
use App\Livewire\User\ReimbursementPage;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Division;
use App\Models\Employee;
use App\Models\FaceDescriptor;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkFromHomeRequest;
use App\Support\ApprovalActorService;
use App\Support\UserHomeCommandCenterService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('notifications page hides dismissed announcements for the current user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $visibleAnnouncement = Announcement::create([
        'title' => 'Visible Notice',
        'content' => 'This announcement should still be visible.',
        'priority' => 'high',
        'published_at' => now(),
        'expired_at' => now()->addDay(),
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    $dismissedAnnouncement = Announcement::create([
        'title' => 'Dismissed Notice',
        'content' => 'This announcement should be hidden.',
        'priority' => 'medium',
        'published_at' => now(),
        'expired_at' => now()->addDay(),
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    $dismissedAnnouncement->dismissedByUsers()->attach($user->id, [
        'dismissed_at' => now(),
    ]);

    Livewire::test(NotificationsPage::class)
        ->assertSee($visibleAnnouncement->title)
        ->assertDontSee($dismissedAnnouncement->title);
});

test('notifications page can mark all unread notifications as read', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => [
            'title' => 'Test Notification',
            'message' => 'Please review this item.',
        ],
    ]);

    expect($user->fresh()->unreadNotifications()->count())->toBe(1);

    Livewire::test(NotificationsPage::class)
        ->call('markAllAsRead');

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

test('reimbursement page filters claims by status and type', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    // Type filter memakai relasi category (code) — fixture wajib punya kategori.
    $medical = ReimbursementCategory::create([
        'company_id' => $user->company_id,
        'name' => 'Medical',
        'code' => 'medical',
        'is_active' => true,
    ]);
    $transport = ReimbursementCategory::create([
        'company_id' => $user->company_id,
        'name' => 'Transport',
        'code' => 'transport',
        'is_active' => true,
    ]);

    Reimbursement::create([
        'employee_id' => $employee->id,
        'category_id' => $medical->id,
        'title' => 'Medical',
        'expense_date' => now()->toDateString(),
        'amount' => 150000,
        'description' => 'Medical reimbursement',
        'status' => 'approved',
    ]);

    Reimbursement::create([
        'employee_id' => $employee->id,
        'category_id' => $transport->id,
        'title' => 'Transport',
        'expense_date' => now()->subDay()->toDateString(),
        'amount' => 50000,
        'description' => 'Transport reimbursement',
        'status' => 'pending',
    ]);

    Livewire::test(ReimbursementPage::class)
        ->set('statusFilter', 'approved')
        ->set('typeFilter', 'medical')
        ->assertSee('Medical reimbursement')
        ->assertDontSee('Transport reimbursement');
});

test('home action needed explains each count and routes to the matching workflow', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    FaceDescriptor::create([
        'employee_id' => $employee->id,
        'embedding' => array_fill(0, 128, 0.1),
        'is_active' => true,
    ]);

    Announcement::create([
        'title' => 'Policy Update',
        'content' => 'Please acknowledge the updated policy.',
        'priority' => 'high',
        'published_at' => now(),
        'expired_at' => now()->addDay(),
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\TestNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => [
            'title' => 'Payslip Paid',
            'message' => 'Your payslip is ready.',
        ],
    ]);

    Reimbursement::create([
        'employee_id' => $employee->id,
        'title' => 'Transport',
        'expense_date' => now()->toDateString(),
        'amount' => 100000,
        'description' => 'Client visit',
        'status' => 'pending',
    ]);

    WorkFromHomeRequest::create([
        'user_id' => $user->id,
        'company_id' => $user->company_id,
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'date' => now()->addDay()->toDateString(),
        'reason' => 'Focus work',
        'status' => WorkFromHomeRequest::STATUS_PENDING,
    ]);

    $home = app(UserHomeCommandCenterService::class)->forUser($user);
    $items = collect($home['actionItems'])->keyBy('label');

    expect($home['attentionCount'])->toBe(4)
        ->and($items->keys()->all())->toContain(__('Notifications'), __('Announcements'), __('Claim'), __('WFH'))
        ->and($items->keys()->all())->not->toContain(__('Requests'))
        ->and($items[__('Notifications')]['href'])->toBe(route('notifications'))
        ->and($items[__('Announcements')]['href'])->toBe(route('notifications'))
        ->and($items[__('Claim')]['href'])->toBe(route('reimbursement'))
        ->and($items[__('WFH')]['href'])->toBe(route('wfh-requests'));
});

test('reimbursement page stores uploaded attachments on private disk', function () {
    Storage::fake('local');
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    // createClaim memerlukan employee record (UserReimbursementService).
    Employee::factory()->create(['user_id' => $user->id]);

    Livewire::test(ReimbursementPage::class)
        ->set('date', now()->toDateString())
        ->set('type', 'medical')
        ->set('amount', 150000)
        ->set('description', 'Medical receipt')
        ->set('attachment', UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'))
        ->call('save');

    $claim = Reimbursement::firstOrFail();

    expect($claim->attachment_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists($claim->attachment_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($claim->attachment_path))->toBeFalse();
});

test('reimbursement page accepts masked rupiah amount', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // createClaim memerlukan employee record (UserReimbursementService).
    Employee::factory()->create(['user_id' => $user->id]);

    Livewire::test(ReimbursementPage::class)
        ->set('date', now()->toDateString())
        ->set('type', 'transport')
        ->set('amount', '99,999,999')
        ->set('description', 'Taxi receipt for client visit')
        ->call('save')
        ->assertHasNoErrors(['amount']);

    expect((int) Reimbursement::firstOrFail()->amount)->toBe(99999999);
});

test('reimbursement attachment download is restricted to owner or admin', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $path = 'reimbursements/receipt.pdf';
    Storage::disk('local')->put($path, 'receipt-file');

    $ownerEmployee = Employee::factory()->create(['user_id' => $owner->id]);

    $claim = Reimbursement::create([
        'employee_id' => $ownerEmployee->id,
        'title' => 'Medical',
        'expense_date' => now()->toDateString(),
        'amount' => 150000,
        'description' => 'Medical reimbursement',
        'attachment_path' => $path,
        'status' => 'pending',
    ]);

    $this->actingAs($owner)
        ->get(route('reimbursement.attachment.download', $claim))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('reimbursement.attachment.download', $claim))
        ->assertOk();

    $this->actingAs($otherUser)
        ->get(route('reimbursement.attachment.download', $claim))
        ->assertForbidden();
});

test('attendance history summary counts inferred absences for past working days', function () {
    Carbon::setTestNow('2026-04-03 09:00:00');

    $user = User::factory()->create();
    $this->actingAs($user);
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    Attendance::create([
        'employee_id' => $employee->id,
        'date' => '2026-04-01',
        'status' => 'on_time',
        'clock_in' => now()->setTime(8, 0),
        'clock_out' => now()->setTime(17, 0),
    ]);

    Livewire::test(AttendanceHistoryComponent::class)
        ->set('selectedYear', '2026')
        ->set('selectedMonth', '04')
        ->assertViewHas('counts', function ($counts) {
            return ($counts['on_time'] ?? null) === 1
                && ($counts['absent'] ?? null) === 1;
        });

    Carbon::setTestNow();
});

test('manager home shows complete team shortcuts', function () {
    $company = Company::create([
        'name' => 'PasPapan Test',
        'slug' => 'paspapan-test',
        'code' => 'PASPAPAN_TEST_'.uniqid(),
        'status' => Company::STATUS_ACTIVE,
        'npwp' => '01.234.567.8-999.000',
        'phone' => '021-1234567',
        'email' => 'test@example.com',
    ]);

    $division = Division::create([
        'name' => 'Operations',
        'code' => 'OPS_'.uniqid(),
    ]);
    $managerLevel = JobLevel::create(['name' => 'Manager', 'rank' => 2]);
    $officerLevel = JobLevel::create(['name' => 'Officer', 'rank' => 4]);
    $managerTitle = JobTitle::create(['name' => 'Manager', 'job_level_id' => $managerLevel->id]);
    $officerTitle = JobTitle::create(['name' => 'Officer', 'job_level_id' => $officerLevel->id]);

    $manager = User::factory()->create([
        'company_id' => $company->id,
    ]);
    Employee::factory()->create(['user_id' => $manager->id, 'division_id' => $division->id]);

    // Manager tools (Team Approvals, Team Attendance, Team Kasbon) digate oleh
    // permission review_subordinate_requests — fixture harus punya role-nya.
    $managerRole = Role::create([
        'name' => 'Manager Audit',
        'slug' => 'manager_audit_'.uniqid(),
        'description' => 'Can review subordinate requests.',
        'permission_keys' => ['review_subordinate_requests'],
    ]);
    $manager->roles()->sync([$managerRole->id]);

    User::factory()->create([
        'company_id' => $company->id,
        'manager_id' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->get(route('home'))
        ->assertOk()
        ->assertSee(__('Manager tools'))
        ->assertSee(__('Team Approvals'))
        ->assertSee(__('Team Attendance'))
        ->assertSee(__('Team Kasbon'));
});

test('head subordinate lookup is company scoped and includes lower division roles', function () {
    $company = Company::create([
        'name' => 'Primary Company',
        'slug' => 'primary-company',
        'code' => 'PRIMARY_'.uniqid(),
        'status' => Company::STATUS_ACTIVE,
        'npwp' => '01.234.567.8-999.000',
        'phone' => '021-1234567',
        'email' => 'primary@example.com',
    ]);
    $otherCompany = Company::create([
        'name' => 'Other Company',
        'slug' => 'other-company',
        'code' => 'OTHER_'.uniqid(),
        'status' => Company::STATUS_ACTIVE,
        'npwp' => '01.234.567.8-999.001',
        'phone' => '021-7654321',
        'email' => 'other@example.com',
    ]);

    $division = Division::create([
        'name' => 'Finance',
        'code' => 'FIN_'.uniqid(),
    ]);
    $headLevel = JobLevel::create(['name' => 'Head', 'rank' => 1]);
    $officerLevel = JobLevel::create(['name' => 'Officer', 'rank' => 4]);
    $headTitle = JobTitle::create(['name' => 'Head', 'job_level_id' => $headLevel->id]);
    $officerTitle = JobTitle::create(['name' => 'Officer', 'job_level_id' => $officerLevel->id]);

    $head = User::factory()->create([
        'company_id' => $company->id,
    ]);
    Employee::factory()->create(['user_id' => $head->id, 'division_id' => $division->id]);

    $sameCompanyOfficer = User::factory()->create([
        'company_id' => $company->id,
        'manager_id' => $head->id,
    ]);

    $otherCompanyOfficer = User::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $subordinateIds = app(ApprovalActorService::class)->subordinateIds($head);

    expect($subordinateIds)
        ->toContain($sameCompanyOfficer->id)
        ->not->toContain($otherCompanyOfficer->id);
});
