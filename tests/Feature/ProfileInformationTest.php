<?php

use App\Models\Employee;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Support\Carbon;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;
use Livewire\Livewire;

test('current profile information is available', function () {
    $this->actingAs($user = User::factory()->create());

    $component = Livewire::test(UpdateProfileInformationForm::class);

    expect($component->state['name'])->toEqual($user->name);
    expect($component->state['email'])->toEqual($user->email);
});

test('profile information can be updated', function () {
    Wilayah::insert([
        ['kode' => '31', 'nama' => 'DKI Jakarta'],
        ['kode' => '31.01', 'nama' => 'Jakarta Pusat'],
        ['kode' => '31.01.01', 'nama' => 'Gambir'],
        ['kode' => '31.01.01.1001', 'nama' => 'Cideng'],
    ]);

    $this->actingAs($user = User::factory()->create());

    Employee::factory()->create(['user_id' => $user->id]);

    Livewire::test(UpdateProfileInformationForm::class)
        ->set('state', [
            'name' => 'Test Name',
            'nip' => '123',
            'email' => 'test@example.com',
            'phone' => '123',
            'gender' => 'female',
            'address' => 'abc',
            'provinsi_kode' => '31',
            'kabupaten_kode' => '31.01',
            'kecamatan_kode' => '31.01.01',
            'kelurahan_kode' => '31.01.01.1001',
            'birth_date' => '2024-01-01',
            'birth_place' => 'abc',
            'division_id' => null,
            'job_title_id' => null,
        ])->call('updateProfileInformation');

    expect($user->fresh())
        ->name->toEqual('Test Name')
        ->email->toEqual('test@example.com')
        ->gender->toEqual('female')
        ->phone->toEqual('123')
        ->nip->toEqual('123')
        ->address->toEqual('abc')
        ->provinsi_kode->toEqual('31')
        ->kabupaten_kode->toEqual('31.01')
        ->kecamatan_kode->toEqual('31.01.01')
        ->kelurahan_kode->toEqual('31.01.01.1001')
        ->birth_date->toEqual(Carbon::parse('2024-01-01'))
        ->birth_place->toEqual('abc')
        ->division_id->toEqual(null)
        ->job_title_id->toEqual(null);
});

// Guard P1 (2026-08-11): komponen profil yang dipakai di /user/profile adalah
// App\Livewire\Profile\UpdateProfileInformationForm (di-override di
// JetstreamServiceProvider), bukan komponen bawaan Jetstream yang mount-nya
// hanya membaca tabel users. Tanpa override, save profil user selalu 500
// (SQLSTATE 23502: null value in column "phone" — employees.phone NOT NULL).
test('profile component (app override) is registered for profile.update-profile-information-form', function () {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test('profile.update-profile-information-form');

    expect($component->instance())->toBeInstanceOf(App\Livewire\Profile\UpdateProfileInformationForm::class);
});

test('profile component mount reads employee-owned columns from employees table', function () {
    $this->actingAs($user = User::factory()->create());

    Employee::factory()->create([
        'user_id' => $user->id,
        'phone' => '081234567890',
        'gender' => 'L',
        'marital_status' => 'married',
    ]);

    Livewire::test(App\Livewire\Profile\UpdateProfileInformationForm::class)
        ->assertSet('state.phone', '081234567890')
        ->assertSet('state.gender', 'male')
        ->assertSet('state.marital_status', 'married');
});

test('profile save persists marital_status without nulling NOT NULL employee columns', function () {
    $this->actingAs($user = User::factory()->create());

    Employee::factory()->create([
        'user_id' => $user->id,
        'phone' => '081234567890',
        'gender' => 'L',
        'marital_status' => 'single',
    ]);

    Livewire::test(App\Livewire\Profile\UpdateProfileInformationForm::class)
        ->set('state.marital_status', 'married')
        ->call('updateProfileInformation');

    $employee = $user->fresh()->employee;

    expect($employee->marital_status->value)->toBe('married')
        ->and($employee->phone)->toBe('081234567890')
        ->and($employee->gender->value)->toBe('L');
});
