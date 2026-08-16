<?php

use App\Livewire\User\MyPerformance;
use App\Models\Appraisal;
use App\Models\AppraisalEvaluation;
use App\Models\Employee;
use App\Models\KpiGroup;
use App\Models\KpiTemplate;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AppraisalActionNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function performanceSetup(): array
{
    $supervisor = User::factory()->create();
    $user = User::factory()->create(['manager_id' => $supervisor->id]);
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $group = KpiGroup::create([
        'name' => 'Kompetensi', 'weight' => 100, 'is_active' => true, 'sort_order' => 1,
    ]);
    $kpi = KpiTemplate::create([
        'kpi_group_id' => $group->id, 'name' => 'Kualitas', 'weight' => 100, 'is_active' => true,
    ]);

    $appraisal = Appraisal::create([
        'employee_id' => $employee->id,
        'reviewer_id' => $employee->id,
        'review_date' => now()->toDateString(),
        'period' => now()->format('Y-m'),
        'status' => 'self_assessment',
    ]);
    $evaluation = AppraisalEvaluation::create([
        'appraisal_id' => $appraisal->id,
        'kpi_template_id' => $kpi->id,
    ]);

    return [$user, $supervisor, $employee, $appraisal, $evaluation];
}

function openAppraisalPeriod(): void
{
    Setting::updateOrCreate(['key' => 'appraisal.period_open'], ['value' => '1']);
    Setting::updateOrCreate(['key' => 'appraisal.period_deadline'], ['value' => now()->addMonth()->toDateString()]);
    Setting::flushCache('appraisal.period_open');
    Setting::flushCache('appraisal.period_deadline');
}

test('employee can view own appraisals page', function () {
    [$user, , , $appraisal] = performanceSetup();

    Livewire::actingAs($user)
        ->test(MyPerformance::class)
        ->assertOk()
        ->assertSee(Carbon::createFromFormat('Y-m', $appraisal->period)->translatedFormat('F Y'));
});

test('self assessment is blocked while period is closed', function () {
    [$user, , , $appraisal] = performanceSetup();

    Livewire::actingAs($user)
        ->test(MyPerformance::class)
        ->call('openSelfAssessment', $appraisal->id)
        ->assertSet('showSelfAssessmentModal', false)
        ->assertSet('activeAppraisalId', null);
});

test('employee can open self assessment when period is open', function () {
    [$user, , , $appraisal] = performanceSetup();
    openAppraisalPeriod();

    Livewire::actingAs($user)
        ->test(MyPerformance::class)
        ->call('openSelfAssessment', $appraisal->id)
        ->assertSet('showSelfAssessmentModal', true)
        ->assertSet('activeAppraisalId', $appraisal->id);
});

test('submitting self assessment forwards appraisal to manager review', function () {
    Notification::fake();

    [$user, $supervisor, , $appraisal, $evaluation] = performanceSetup();
    openAppraisalPeriod();

    Livewire::actingAs($user)
        ->test(MyPerformance::class)
        ->call('openSelfAssessment', $appraisal->id)
        ->set('selfScores.'.$evaluation->id, 4)
        ->set('evidenceDescriptions.'.$evaluation->id, 'Semua target tercapai')
        ->call('submitSelfAssessment')
        ->assertHasNoErrors()
        ->assertSet('showSelfAssessmentModal', false);

    $appraisal->refresh();

    expect($appraisal->status)->toBe('manager_review')
        ->and($evaluation->fresh()->self_score)->toBe('80.00')
        ->and($evaluation->fresh()->comments)->toBe('Semua target tercapai');

    Notification::assertSentTo($supervisor, AppraisalActionNotification::class);
});

test('employee cannot self assess another person appraisal', function () {
    [$user] = performanceSetup();
    openAppraisalPeriod();

    $otherUser = User::factory()->create();
    $otherEmployee = Employee::factory()->create(['user_id' => $otherUser->id]);
    $otherAppraisal = Appraisal::create([
        'employee_id' => $otherEmployee->id,
        'reviewer_id' => $otherEmployee->id,
        'review_date' => now()->toDateString(),
        'period' => now()->format('Y-m'),
        'status' => 'self_assessment',
    ]);

    Livewire::actingAs($user)
        ->test(MyPerformance::class)
        ->call('openSelfAssessment', $otherAppraisal->id)
        ->assertSet('showSelfAssessmentModal', false)
        ->assertSet('activeAppraisalId', null);
});

test('employee can acknowledge completed appraisal', function () {
    [$user, , $employee] = performanceSetup();

    $completed = Appraisal::create([
        'employee_id' => $employee->id,
        'reviewer_id' => $employee->id,
        'review_date' => now()->toDateString(),
        'period' => now()->subMonth()->format('Y-m'),
        'status' => 'completed',
    ]);

    Livewire::actingAs($user)
        ->test(MyPerformance::class)
        ->call('acknowledge', $completed->id)
        ->assertHasNoErrors();

    expect($completed->fresh()->employee_acknowledgement)->toBeTrue();
});
