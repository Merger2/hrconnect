<?php

use App\Http\Requests\Api\ListAttendanceRequest;
use App\Http\Requests\Api\ListLeaveRequest;
use App\Http\Requests\Api\ListOvertimeRequest;
use App\Http\Requests\Api\ListPayrollRequest;
use App\Http\Requests\Api\ListReimbursementRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use Illuminate\Support\Facades\Validator;

function validateOrFail(string $requestClass, array $data): void
{
    $request = new $requestClass;
    $validator = Validator::make($data, $request->rules());

    if ($validator->fails()) {
        throw new \Illuminate\Validation\ValidationException($validator);
    }
}

it('validates UpdateProfileRequest', function () {
    validateOrFail(UpdateProfileRequest::class, [
        'phone' => '08123456789',
        'address_detail' => 'Jl. Test No. 1',
        'bank_name' => 'BCA',
        'bank_account_number' => '12345678',
    ]);

    expect(fn () => validateOrFail(UpdateProfileRequest::class, [
        'phone' => 'invalid-phone',
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(fn () => validateOrFail(UpdateProfileRequest::class, [
        'bank_account_number' => '123',
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(fn () => validateOrFail(UpdateProfileRequest::class, [
        'address_detail' => str_repeat('a', 501),
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('validates ListAttendanceRequest', function () {
    validateOrFail(ListAttendanceRequest::class, [
        'period' => '2024-01',
        'status' => 'present',
        'page' => 1,
        'per_page' => 50,
    ]);

    expect(fn () => validateOrFail(ListAttendanceRequest::class, [
        'period' => 'invalid',
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(fn () => validateOrFail(ListAttendanceRequest::class, [
        'per_page' => 200,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(fn () => validateOrFail(ListAttendanceRequest::class, [
        'page' => 0,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('validates ListLeaveRequest', function () {
    validateOrFail(ListLeaveRequest::class, [
        'status' => 'approved',
        'year' => 2024,
        'employee_id' => 1,
        'page' => 2,
        'per_page' => 25,
    ]);

    expect(fn () => validateOrFail(ListLeaveRequest::class, [
        'year' => 1999,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(fn () => validateOrFail(ListLeaveRequest::class, [
        'year' => 2101,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(fn () => validateOrFail(ListLeaveRequest::class, [
        'per_page' => 0,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('validates ListOvertimeRequest', function () {
    validateOrFail(ListOvertimeRequest::class, [
        'status' => 'pending',
        'period' => '2024-06',
        'page' => 1,
        'per_page' => 10,
    ]);

    expect(fn () => validateOrFail(ListOvertimeRequest::class, [
        'period' => 'not-a-period',
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(fn () => validateOrFail(ListOvertimeRequest::class, [
        'per_page' => 101,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('validates ListPayrollRequest', function () {
    validateOrFail(ListPayrollRequest::class, [
        'year' => 2025,
        'employee_id' => 1,
        'page' => 3,
        'per_page' => 75,
    ]);

    expect(fn () => validateOrFail(ListPayrollRequest::class, [
        'year' => 1999,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(fn () => validateOrFail(ListPayrollRequest::class, [
        'page' => -1,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('validates ListReimbursementRequest', function () {
    validateOrFail(ListReimbursementRequest::class, [
        'status' => 'approved',
        'period' => '2024-12',
        'page' => 1,
        'per_page' => 50,
    ]);

    expect(fn () => validateOrFail(ListReimbursementRequest::class, [
        'period' => 'bad',
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(fn () => validateOrFail(ListReimbursementRequest::class, [
        'per_page' => 1000,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});
