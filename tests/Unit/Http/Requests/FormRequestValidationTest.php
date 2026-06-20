<?php

use App\Http\Requests\Api\ChatRequest;
use App\Http\Requests\Api\ExportMonthlyRequest;
use App\Http\Requests\Api\ExportPeriodRequest;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\ListAttendanceRequest;
use App\Http\Requests\Api\ListLeaveRequest;
use App\Http\Requests\Api\ListOvertimeRequest;
use App\Http\Requests\Api\ListPayrollRequest;
use App\Http\Requests\Api\ListReimbursementRequest;
use App\Http\Requests\Api\PendingApprovalsRequest;
use App\Http\Requests\Api\RegisterFaceRequest;
use App\Http\Requests\Api\StoreOvertimeRequest;
use App\Http\Requests\Api\TwoFactorChallengeRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Requests\Api\UploadDocumentRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

// ─── ForgotPasswordRequest ───────────────────────────────────────────

test('ForgotPasswordRequest accepts valid email', function () {
    $v = validate(new ForgotPasswordRequest, ['email' => 'user@example.com']);
    expect($v->passes())->toBeTrue();
});

test('ForgotPasswordRequest rejects missing email', function () {
    $v = validate(new ForgotPasswordRequest, []);
    expect($v->fails())->toBeTrue();
});

test('ForgotPasswordRequest rejects invalid email format', function () {
    $v = validate(new ForgotPasswordRequest, ['email' => 'not-an-email']);
    expect($v->fails())->toBeTrue();
});

// ─── TwoFactorChallengeRequest ───────────────────────────────────────

test('TwoFactorChallengeRequest accepts valid challenge data', function () {
    $v = validate(new TwoFactorChallengeRequest, [
        'challenge_id' => 'abc123',
        'code' => '123456',
    ]);
    expect($v->passes())->toBeTrue();
});

test('TwoFactorChallengeRequest rejects missing fields', function () {
    $v1 = validate(new TwoFactorChallengeRequest, []);
    expect($v1->fails())->toBeTrue();

    $v2 = validate(new TwoFactorChallengeRequest, ['challenge_id' => 'abc']);
    expect($v2->fails())->toBeTrue();

    $v3 = validate(new TwoFactorChallengeRequest, ['code' => '123456']);
    expect($v3->fails())->toBeTrue();
});

// ─── PendingApprovalsRequest ─────────────────────────────────────────

test('PendingApprovalsRequest accepts valid params', function () {
    $v = validate(new PendingApprovalsRequest, [
        'type' => 'leave',
        'page' => 1,
        'per_page' => 25,
    ]);
    expect($v->passes())->toBeTrue();
});

test('PendingApprovalsRequest accepts empty params', function () {
    $v = validate(new PendingApprovalsRequest, []);
    expect($v->passes())->toBeTrue();
});

test('PendingApprovalsRequest rejects invalid type', function () {
    $v = validate(new PendingApprovalsRequest, ['type' => 'invalid_type']);
    expect($v->fails())->toBeTrue();
});

test('PendingApprovalsRequest rejects per_page beyond max', function () {
    $v = validate(new PendingApprovalsRequest, ['per_page' => 101]);
    expect($v->fails())->toBeTrue();
});

// ─── ExportPeriodRequest ─────────────────────────────────────────────

test('ExportPeriodRequest accepts valid period', function () {
    $v = validate(new ExportPeriodRequest, ['period' => '2026-06']);
    expect($v->passes())->toBeTrue();
});

test('ExportPeriodRequest rejects missing period', function () {
    $v = validate(new ExportPeriodRequest, []);
    expect($v->fails())->toBeTrue();
});

test('ExportPeriodRequest rejects invalid period format', function () {
    $v = validate(new ExportPeriodRequest, ['period' => 'invalid']);
    expect($v->fails())->toBeTrue();
});

// ─── ExportMonthlyRequest ────────────────────────────────────────────

test('ExportMonthlyRequest accepts valid period', function () {
    $v = validate(new ExportMonthlyRequest, ['period' => '2026-06']);
    expect($v->passes())->toBeTrue();
});

test('ExportMonthlyRequest accepts period with branch_id', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();

    $v = validate(new ExportMonthlyRequest, [
        'period' => '2026-06',
        'branch_id' => $branch->id,
    ]);
    expect($v->passes())->toBeTrue();
});

test('ExportMonthlyRequest rejects non-existent branch_id', function () {
    $v = validate(new ExportMonthlyRequest, [
        'period' => '2026-06',
        'branch_id' => 99999,
    ]);
    expect($v->fails())->toBeTrue();
});

test('ExportMonthlyRequest rejects invalid period', function () {
    $v = validate(new ExportMonthlyRequest, ['period' => 'not-valid']);
    expect($v->fails())->toBeTrue();
});

// ─── ChatRequest ─────────────────────────────────────────────────────

test('ChatRequest accepts valid question', function () {
    $v = validate(new ChatRequest, ['question' => 'Bagaimana cara mengajukan cuti?']);
    expect($v->passes())->toBeTrue();
});

test('ChatRequest rejects missing question', function () {
    $v = validate(new ChatRequest, []);
    expect($v->fails())->toBeTrue();
});

test('ChatRequest rejects question too short', function () {
    $v = validate(new ChatRequest, ['question' => 'abc']);
    expect($v->fails())->toBeTrue();
});

test('ChatRequest rejects question too long', function () {
    $v = validate(new ChatRequest, ['question' => str_repeat('a', 501)]);
    expect($v->fails())->toBeTrue();
});

// ─── UploadDocumentRequest ───────────────────────────────────────────

test('UploadDocumentRequest accepts valid pdf file', function () {
    $v = validate(new UploadDocumentRequest, [
        'title' => 'HR Policy Document',
        'category' => 'hr_policy',
        'file' => UploadedFile::fake()->create('policy.pdf', 500),
    ]);
    expect($v->passes())->toBeTrue();
});

test('UploadDocumentRequest accepts without category', function () {
    $v = validate(new UploadDocumentRequest, [
        'title' => 'General Document',
        'file' => UploadedFile::fake()->create('doc.pdf', 1000),
    ]);
    expect($v->passes())->toBeTrue();
});

test('UploadDocumentRequest rejects invalid category', function () {
    $v = validate(new UploadDocumentRequest, [
        'title' => 'Test',
        'category' => 'invalid_category',
        'file' => UploadedFile::fake()->create('test.pdf', 100),
    ]);
    expect($v->fails())->toBeTrue();
});

test('UploadDocumentRequest rejects non-pdf file', function () {
    $v = validate(new UploadDocumentRequest, [
        'title' => 'Test',
        'file' => UploadedFile::fake()->create('image.png', 100),
    ]);
    expect($v->fails())->toBeTrue();
});

test('UploadDocumentRequest rejects file over 10MB', function () {
    $v = validate(new UploadDocumentRequest, [
        'title' => 'Test',
        'file' => UploadedFile::fake()->create('large.pdf', 11000),
    ]);
    expect($v->fails())->toBeTrue();
});

// ─── StoreOvertimeRequest ──────────────────────────────────────────

test('StoreOvertimeRequest accepts valid overtime data', function () {
    $v = validate(new StoreOvertimeRequest, [
        'date' => now()->format('Y-m-d'),
        'start_time' => '18:00',
        'end_time' => '20:30',
        'description' => 'Menangani insiden produksi.',
    ]);
    expect($v->passes())->toBeTrue();
});

test('StoreOvertimeRequest rejects missing fields', function () {
    $v = validate(new StoreOvertimeRequest, []);
    expect($v->fails())->toBeTrue();
});

test('StoreOvertimeRequest rejects invalid time format', function () {
    $v = validate(new StoreOvertimeRequest, [
        'date' => now()->format('Y-m-d'),
        'start_time' => '6pm',
        'end_time' => '20:30',
        'description' => 'Test',
    ]);
    expect($v->fails())->toBeTrue();
});

test('StoreOvertimeRequest rejects description too short', function () {
    $v = validate(new StoreOvertimeRequest, [
        'date' => now()->format('Y-m-d'),
        'start_time' => '18:00',
        'end_time' => '20:00',
        'description' => 'Short',
    ]);
    expect($v->fails())->toBeTrue();
});

// ─── RegisterFaceRequest ────────────────────────────────────────────

test('RegisterFaceRequest accepts valid 128D face embedding', function () {
    $v = validate(new RegisterFaceRequest, [
        'embedding' => array_fill(0, 128, 0.01),
    ]);

    expect($v->passes())->toBeTrue();
});

test('RegisterFaceRequest rejects wrong-size embedding', function () {
    $v = validate(new RegisterFaceRequest, ['embedding' => array_fill(0, 64, 0.01)]);
    expect($v->fails())->toBeTrue();

    $v2 = validate(new RegisterFaceRequest, ['embedding' => array_fill(0, 256, 0.01)]);
    expect($v2->fails())->toBeTrue();
});

test('RegisterFaceRequest rejects values outside -1.5 to 1.5 range', function () {
    $embedding = array_fill(0, 128, 0.01);
    $embedding[0] = 2.0;
    $v = validate(new RegisterFaceRequest, ['embedding' => $embedding]);
    expect($v->fails())->toBeTrue();

    $embedding[0] = -2.0;
    $v2 = validate(new RegisterFaceRequest, ['embedding' => $embedding]);
    expect($v2->fails())->toBeTrue();
});

test('RegisterFaceRequest accepts values exactly at boundaries', function () {
    $embedding = array_fill(0, 128, 0.01);
    $embedding[0] = -1.5;
    $embedding[1] = 1.5;
    $v = validate(new RegisterFaceRequest, ['embedding' => $embedding]);
    expect($v->passes())->toBeTrue();
});

test('RegisterFaceRequest rejects non-numeric embedding values', function () {
    $embedding = array_fill(0, 128, 0.01);
    $embedding[50] = 'abc';
    $v = validate(new RegisterFaceRequest, ['embedding' => $embedding]);
    expect($v->fails())->toBeTrue();
});
