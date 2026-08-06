<?php

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\User;
use App\Support\SecureUploadPolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

test('attachment matrix rejects unsafe names and denies cross user reimbursement downloads', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    Storage::disk('local')->put('reimbursements/private.pdf', 'private');

    $ownerEmployee = Employee::factory()->create(['user_id' => $owner->id]);
    $reimbursement = Reimbursement::create([
        'employee_id' => $ownerEmployee->id,
        'title' => 'Medical',
        'expense_date' => now()->toDateString(),
        'amount' => 100000,
        'description' => 'Private claim',
        'attachment_path' => 'reimbursements/private.pdf',
        'status' => 'pending',
    ]);

    $file = UploadedFile::fake()->create('claim.php.pdf', 64, 'application/pdf');
    $rules = app(SecureUploadPolicy::class)->rules('document');

    expect(validator(['attachment' => $file], ['attachment' => ['required', ...$rules]])->fails())->toBeTrue();

    $this->actingAs($attacker)
        ->get(route('reimbursement.attachment.download', $reimbursement))
        ->assertForbidden();
});

test('payslip and payroll privacy matrix denies other users and unauthorized admins', function () {

    $owner = User::factory()->create();
    $ownerEmployee = Employee::factory()->create(['user_id' => $owner->id]);
    $attacker = User::factory()->create();
    $plainAdmin = User::factory()->admin()->create();

    $payroll = Payroll::create([
        'employee_id' => $ownerEmployee->id,
        'period' => now()->format('Y-m'),
        'basic_salary' => 1000000,
        'total_allowance' => 0,
        'gross_salary' => 1000000,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 1000000,
        'status' => 'paid',
    ]);

    expect(Gate::forUser($owner)->allows('download', $payroll))->toBeTrue()
        ->and(Gate::forUser($attacker)->denies('download', $payroll))->toBeTrue()
        ->and(Gate::forUser($plainAdmin)->denies('view', $payroll))->toBeTrue();
});
