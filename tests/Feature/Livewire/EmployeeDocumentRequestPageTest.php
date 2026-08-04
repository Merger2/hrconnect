<?php

use App\Livewire\User\EmployeeDocumentRequestPage;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->withHeaders(['X-Testing' => 'true']);
});

test('EmployeeDocumentRequestPage renders successfully', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(EmployeeDocumentRequestPage::class)
        ->assertStatus(200);
});

test('EmployeeDocumentRequestPage [scenario] [create action]', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(EmployeeDocumentRequestPage::class)
        ->call('create')
        ->assertSet('showModal', true);
});

test('EmployeeDocumentRequestPage [scenario] [close action]', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(EmployeeDocumentRequestPage::class)
        ->set('documentType', 'ktp')
        ->set('showModal', true)
        ->call('close')
        ->assertSet('showModal', false)
        ->assertSet('documentType', null);
});

test('EmployeeDocumentRequestPage [scenario] [form validation]', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(EmployeeDocumentRequestPage::class)
        ->call('create')
        ->call('store')
        ->assertHasErrors(['documentType', 'purpose']);
});
