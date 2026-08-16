<?php

use App\Livewire\Admin\Settings\KpiSettings;
use App\Models\KpiGroup;
use App\Models\KpiTemplate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function kpiSettingsAdmin(): User
{
    return User::factory()->admin(true)->create();
}

test('superadmin can render kpi settings with defaults', function () {
    $this->actingAs(kpiSettingsAdmin());

    Livewire::test(KpiSettings::class)
        ->assertOk()
        ->assertSet('attendanceWeight', 30)
        ->assertSet('periodOpen', false);
});

test('kpi settings denies users without manage_kpi_settings permission', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(KpiSettings::class)
        ->assertForbidden();
});

test('admin can create kpi group', function () {
    $this->actingAs(kpiSettingsAdmin());

    Livewire::test(KpiSettings::class)
        ->call('createGroup')
        ->set('groupName', 'Kualitas Kerja')
        ->set('groupWeight', 40)
        ->call('saveGroup')
        ->assertHasNoErrors()
        ->assertSet('showGroupModal', false);

    $group = KpiGroup::query()->where('name', 'Kualitas Kerja')->first();

    expect($group)->not->toBeNull()
        ->and($group->weight)->toBe(40.0)
        ->and($group->is_active)->toBeTrue()
        ->and($group->sort_order)->toBe(1);
});

test('admin can edit and delete empty kpi group', function () {
    $this->actingAs(kpiSettingsAdmin());

    $group = KpiGroup::create([
        'name' => 'Lama', 'weight' => 10, 'is_active' => true, 'sort_order' => 1,
    ]);

    Livewire::test(KpiSettings::class)
        ->call('editGroup', $group->id)
        ->set('groupName', 'Baru')
        ->call('saveGroup')
        ->assertHasNoErrors();

    expect($group->fresh()->name)->toBe('Baru');

    Livewire::test(KpiSettings::class)
        ->call('deleteGroup', $group->id)
        ->assertHasNoErrors();

    expect(KpiGroup::find($group->id))->toBeNull();
});

test('admin can create kpi template under a group', function () {
    $this->actingAs(kpiSettingsAdmin());

    $group = KpiGroup::create([
        'name' => 'Kompetensi', 'weight' => 60, 'is_active' => true, 'sort_order' => 1,
    ]);

    Livewire::test(KpiSettings::class)
        ->call('createTemplate', $group->id)
        ->set('name', 'Ketepatan waktu')
        ->set('indicator_description', 'Menyelesaikan tugas sesuai deadline')
        ->set('weight', 50)
        ->call('save')
        ->assertHasNoErrors();

    $kpi = KpiTemplate::query()->where('name', 'Ketepatan waktu')->first();

    expect($kpi)->not->toBeNull()
        ->and($kpi->kpi_group_id)->toBe($group->id)
        ->and($kpi->weight)->toBe(50.0);
});

test('group with templates cannot be deleted before its templates', function () {
    $this->actingAs(kpiSettingsAdmin());

    $group = KpiGroup::create([
        'name' => 'Terisi', 'weight' => 30, 'is_active' => true, 'sort_order' => 1,
    ]);
    KpiTemplate::create([
        'kpi_group_id' => $group->id, 'name' => 'KPI A', 'weight' => 100, 'is_active' => true,
    ]);

    Livewire::test(KpiSettings::class)
        ->call('deleteGroup', $group->id)
        ->assertHasNoErrors();

    expect(KpiGroup::find($group->id))->not->toBeNull();
});

test('admin can save period lock and evaluation settings', function () {
    $this->actingAs(kpiSettingsAdmin());

    Livewire::test(KpiSettings::class)
        ->set('periodLabel', 'Periode 2026')
        ->set('periodDeadline', '2026-12-31')
        ->call('savePeriodLock')
        ->assertHasNoErrors();

    expect(Setting::getValue('appraisal.period_label'))->toBe('Periode 2026')
        ->and(Setting::getValue('appraisal.period_deadline'))->toBe('2026-12-31');

    Livewire::test(KpiSettings::class)
        ->set('attendanceWeight', 45)
        ->call('saveEvaluationSettings')
        ->assertHasNoErrors();

    expect(Setting::getValue('appraisal.attendance_weight'))->toBe('45');
});

test('admin can toggle period lock', function () {
    $this->actingAs(kpiSettingsAdmin());

    Livewire::test(KpiSettings::class)
        ->call('togglePeriodLock')
        ->assertSet('periodOpen', true);

    expect(Setting::getValue('appraisal.period_open'))->toBe('1');
});
