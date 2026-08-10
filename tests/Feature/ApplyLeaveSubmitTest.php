<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function applyLeaveEmployee(): User
{
    $user = User::factory()->create();
    Employee::factory()->create(['user_id' => $user->id]);

    return $user;
}

test('employee can submit leave request via web', function () {
    $user = applyLeaveEmployee();

    $this->actingAs($user)
        ->post('/apply-leave', [
            'status' => 'excused',
            'note' => 'Izin sakit karena demam tinggi',
            'from' => now()->toDateString(),
            'attachment' => UploadedFile::fake()->create('izin.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Attendance::query()->where('employee_id', $user->employee->id)->exists())->toBeTrue();
});

test('leave submission requires note and from date', function () {
    $user = applyLeaveEmployee();

    $this->actingAs($user)
        ->post('/apply-leave', [
            'status' => 'excused',
        ])
        ->assertSessionHasErrors(['note', 'from']);
});

test('leave submission requires attachment when policy enforces it', function () {
    $user = applyLeaveEmployee();

    $this->actingAs($user)
        ->post('/apply-leave', [
            'status' => 'excused',
            'note' => 'Izin sakit karena demam tinggi',
            'from' => now()->toDateString(),
        ])
        ->assertSessionHasErrors(['attachment']);
});

test('guest is redirected to login when submitting leave', function () {
    $this->from('/apply-leave')
        ->post('/apply-leave', [
            'status' => 'excused',
            'note' => 'Izin sakit',
            'from' => now()->toDateString(),
        ])
        ->assertRedirect('/login');
});
