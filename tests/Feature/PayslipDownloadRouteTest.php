<?php

use App\Enums\PayrollStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;

/**
 * P1 fix 2026-08-11: route web `payslip.download` (pengganti dispatch
 * 'download-file' yang tidak punya listener). Guard: PayrollPolicy::download
 * (kepemilikan + status approved/paid) — menutup IDOR jalur Livewire lama.
 *
 * Keputusan Fikih 2026-08-11: "Minta PIN saat download (paling aman)".
 * - Pemilik payslip harus memasukkan PIN saat download; PDF di-enkripsi dengan
 *   PIN plaintext (fix akar masalah: sebelumnya PDF di-enkripsi dgn hash
 *   argon2id sehingga tidak bisa dibuka karyawan).
 * - Staff/admin terotorisasi dapat file langsung tanpa PIN prompt.
 */
function payslipDownloadPayrolls(): array
{
    $company = Company::factory()->create();

    $owner = User::factory()->create();
    $ownerEmployee = Employee::factory()->for($company)->create([
        'user_id' => $owner->id,
        // Cast 'hashed' meng-hash otomatis — isi plaintext, bukan pre-hash.
        'payslip_password' => '4321',
    ]);

    $other = User::factory()->create();
    $otherEmployee = Employee::factory()->for($company)->create(['user_id' => $other->id]);

    $paid = Payroll::factory()->create([
        'employee_id' => $ownerEmployee->id,
        'period' => '2026-07',
        'net_salary' => 6250000,
        'status' => PayrollStatus::PAID,
    ]);

    $draft = Payroll::factory()->create([
        'employee_id' => $ownerEmployee->id,
        'period' => '2026-08',
        'net_salary' => 5000000,
        'status' => PayrollStatus::DRAFT,
    ]);

    return [$owner, $other, $paid, $draft];
}

test('owner sees PIN prompt before downloading payslip', function () {
    [$owner, , $paid] = payslipDownloadPayrolls();

    $this->actingAs($owner)
        ->get(route('payslip.download', $paid))
        ->assertOk()
        ->assertViewIs('payroll.payslip-pin')
        ->assertSee('name="pin"', false);
});

test('owner downloads paid payslip with correct PIN (encrypted PDF)', function () {
    [$owner, , $paid] = payslipDownloadPayrolls();

    $this->actingAs($owner)
        ->post(route('payslip.download', $paid), ['pin' => '4321'])
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('owner rejected with wrong PIN', function () {
    [$owner, , $paid] = payslipDownloadPayrolls();

    $this->actingAs($owner)
        ->post(route('payslip.download', $paid), ['pin' => '9999'])
        ->assertSessionHasErrors('pin');
});

test('owner without payslip password is redirected to setup', function () {
    $company = Company::factory()->create();

    // Pemilik TANPA payslip_password (kolom null) — override default faker.
    $owner = User::factory()->create();
    $ownerEmployee = Employee::factory()->for($company)->create([
        'user_id' => $owner->id,
        'payslip_password' => null,
        'payslip_password_set_at' => null,
    ]);

    $paid = Payroll::factory()->create([
        'employee_id' => $ownerEmployee->id,
        'period' => '2026-07',
        'status' => PayrollStatus::PAID,
    ]);

    $this->actingAs($owner)
        ->get(route('payslip.download', $paid))
        ->assertRedirect(route('my-payslips'));

    // POST tanpa password juga ditolak (branch ! hasValidPayslipPassword di store()).
    $this->actingAs($owner)
        ->post(route('payslip.download', $paid), ['pin' => '4321'])
        ->assertSessionHasErrors('pin');
});

test('employee cannot download another employee payslip (IDOR guard)', function () {
    [, $other, $paid] = payslipDownloadPayrolls();

    $this->actingAs($other)
        ->get(route('payslip.download', $paid))
        ->assertForbidden();

    $this->actingAs($other)
        ->post(route('payslip.download', $paid), ['pin' => '4321'])
        ->assertForbidden();
});

test('draft payroll cannot be downloaded', function () {
    [$owner, , , $draft] = payslipDownloadPayrolls();

    $this->actingAs($owner)
        ->get(route('payslip.download', $draft))
        ->assertForbidden();
});

test('guest cannot download payslip', function () {
    [, , $paid] = payslipDownloadPayrolls();

    $this->get(route('payslip.download', $paid))->assertRedirect(route('login'));
});

test('authorized admin downloads payslip directly without PIN prompt', function () {
    $company = Company::factory()->create();

    $owner = User::factory()->create();
    $ownerEmployee = Employee::factory()->for($company)->create([
        'user_id' => $owner->id,
        // Cast 'hashed' meng-hash otomatis — isi plaintext, bukan pre-hash.
        'payslip_password' => '4321',
    ]);

    $admin = User::factory()->create(['group' => 'superadmin']);

    $paid = Payroll::factory()->create([
        'employee_id' => $ownerEmployee->id,
        'period' => '2026-07',
        'net_salary' => 6250000,
        'status' => PayrollStatus::PAID,
    ]);

    $this->actingAs($admin)
        ->get(route('payslip.download', $paid))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
