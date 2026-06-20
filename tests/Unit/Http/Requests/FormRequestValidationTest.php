<?php

use App\Http\Requests\Api\ListAttendanceRequest;
use App\Http\Requests\Api\ListLeaveRequest;
use App\Http\Requests\Api\ListOvertimeRequest;
use App\Http\Requests\Api\ListPayrollRequest;
use App\Http\Requests\Api\ListReimbursementRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

function validate(FormRequest $request, array $data): Illuminate\Validation\Validator
{
    $request->setContainer(app());
    $request->merge($data);

    return Validator::make($data, $request->rules());
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// ─── UpdateProfileRequest ────────────────────────────────────────────

test('UpdateProfileRequest accepts valid profile data', function () {
    $v = validate(new UpdateProfileRequest, [
        'phone' => '+628123456789',
        'address_detail' => 'Jl. Sudirman No. 1',
        'bank_name' => 'BCA',
        'bank_account_number' => '12345678',
    ]);

    expect($v->passes())->toBeTrue();
});

test('UpdateProfileRequest rejects invalid phone', function () {
    $v = validate(new UpdateProfileRequest, ['phone' => '8123456789']);
    expect($v->fails())->toBeTrue();

    $v2 = validate(new UpdateProfileRequest, ['phone' => '081']);
    expect($v2->fails())->toBeTrue();

    $v3 = validate(new UpdateProfileRequest, ['phone' => '+6281234567890123']);
    expect($v3->fails())->toBeTrue();
});

test('UpdateProfileRequest rejects address_detail over 500 chars', function () {
    $v = validate(new UpdateProfileRequest, ['address_detail' => str_repeat('a', 501)]);
    expect($v->fails())->toBeTrue();
});

test('UpdateProfileRequest rejects invalid bank_account_number', function () {
    $v = validate(new UpdateProfileRequest, ['bank_account_number' => '1234567']);
    expect($v->fails())->toBeTrue();

    $v2 = validate(new UpdateProfileRequest, ['bank_account_number' => '1234567a']);
    expect($v2->fails())->toBeTrue();

    $v3 = validate(new UpdateProfileRequest, ['bank_account_number' => '1234567890123456789']);
    expect($v3->fails())->toBeTrue();
});

// ─── ListAttendanceRequest ───────────────────────────────────────────

test('ListAttendanceRequest accepts valid params', function () {
    $v = validate(new ListAttendanceRequest, [
        'period' => '2024-06',
        'status' => 'present',
        'page' => 1,
        'per_page' => 50,
    ]);

    expect($v->passes())->toBeTrue();
});

test('ListAttendanceRequest rejects invalid period format', function () {
    $v = validate(new ListAttendanceRequest, ['period' => '2024-6']);
    expect($v->fails())->toBeTrue();

    $v2 = validate(new ListAttendanceRequest, ['period' => '2024/06']);
    expect($v2->fails())->toBeTrue();

    $v3 = validate(new ListAttendanceRequest, ['period' => 'invalid']);
    expect($v3->fails())->toBeTrue();
});

test('ListAttendanceRequest rejects invalid per_page', function () {
    $v = validate(new ListAttendanceRequest, ['per_page' => 0]);
    expect($v->fails())->toBeTrue();

    $v2 = validate(new ListAttendanceRequest, ['per_page' => 101]);
    expect($v2->fails())->toBeTrue();

    $v3 = validate(new ListAttendanceRequest, ['per_page' => 'abc']);
    expect($v3->fails())->toBeTrue();
});

test('ListAttendanceRequest rejects non-integer page', function () {
    $v = validate(new ListAttendanceRequest, ['page' => 0]);
    expect($v->fails())->toBeTrue();

    $v2 = validate(new ListAttendanceRequest, ['page' => 'abc']);
    expect($v2->fails())->toBeTrue();
});

// ─── ListLeaveRequest ────────────────────────────────────────────────

test('ListLeaveRequest accepts valid params', function () {
    $v = validate(new ListLeaveRequest, [
        'status' => 'approved',
        'year' => 2024,
        'employee_id' => 1,
        'page' => 2,
        'per_page' => 25,
    ]);

    expect($v->passes())->toBeTrue();
});

test('ListLeaveRequest rejects invalid year range', function () {
    $v = validate(new ListLeaveRequest, ['year' => 1999]);
    expect($v->fails())->toBeTrue();

    $v2 = validate(new ListLeaveRequest, ['year' => 2101]);
    expect($v2->fails())->toBeTrue();

    $v3 = validate(new ListLeaveRequest, ['year' => 'abc']);
    expect($v3->fails())->toBeTrue();
});

test('ListLeaveRequest rejects non-integer employee_id', function () {
    $v = validate(new ListLeaveRequest, ['employee_id' => 'abc']);
    expect($v->fails())->toBeTrue();
});

test('ListLeaveRequest per_page respects max 100', function () {
    $v = validate(new ListLeaveRequest, ['per_page' => 101]);
    expect($v->fails())->toBeTrue();

    $v2 = validate(new ListLeaveRequest, ['per_page' => 100]);
    expect($v2->passes())->toBeTrue();
});

// ─── ListOvertimeRequest ─────────────────────────────────────────────

test('ListOvertimeRequest accepts valid params', function () {
    $v = validate(new ListOvertimeRequest, [
        'status' => 'pending',
        'period' => '2024-06',
        'page' => 1,
        'per_page' => 50,
    ]);

    expect($v->passes())->toBeTrue();
});

test('ListOvertimeRequest rejects invalid period', function () {
    $v = validate(new ListOvertimeRequest, ['period' => 'invalid']);
    expect($v->fails())->toBeTrue();
});

test('ListOvertimeRequest rejects per_page beyond max', function () {
    $v = validate(new ListOvertimeRequest, ['per_page' => 200]);
    expect($v->fails())->toBeTrue();
});

// ─── ListPayrollRequest ──────────────────────────────────────────────

test('ListPayrollRequest accepts valid params', function () {
    $v = validate(new ListPayrollRequest, [
        'year' => 2024,
        'employee_id' => 5,
        'page' => 1,
        'per_page' => 50,
    ]);

    expect($v->passes())->toBeTrue();
});

test('ListPayrollRequest accepts empty params', function () {
    $v = validate(new ListPayrollRequest, []);
    expect($v->passes())->toBeTrue();
});

test('ListPayrollRequest rejects invalid year', function () {
    $v = validate(new ListPayrollRequest, ['year' => 'abc']);
    expect($v->fails())->toBeTrue();
});

// ─── ListReimbursementRequest ────────────────────────────────────────

test('ListReimbursementRequest accepts valid params', function () {
    $v = validate(new ListReimbursementRequest, [
        'status' => 'approved',
        'period' => '2024-06',
        'page' => 1,
        'per_page' => 50,
    ]);

    expect($v->passes())->toBeTrue();
});

test('ListReimbursementRequest rejects invalid period', function () {
    $v = validate(new ListReimbursementRequest, ['period' => 'not-a-period']);
    expect($v->fails())->toBeTrue();
});

test('ListReimbursementRequest rejects per_page beyond max', function () {
    $v = validate(new ListReimbursementRequest, ['per_page' => 999]);
    expect($v->fails())->toBeTrue();
});

test('ListReimbursementRequest rejects non-integer page', function () {
    $v = validate(new ListReimbursementRequest, ['page' => 'not-a-number']);
    expect($v->fails())->toBeTrue();
});
