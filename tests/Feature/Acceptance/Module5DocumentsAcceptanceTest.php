<?php

use App\Livewire\User\EmployeeDocumentRequestPage;
use App\Models\Employee;
use App\Models\EmployeeDocumentType;
use App\Models\User;
use Livewire\Livewire;

/**
 * Acceptance — Modul 5: Dokumen & HR Checklist (PRD §Modul 5)
 *
 * Cakupan checklist:
 * - [ ] Repository dokumen karyawan dengan kategori dan permission → suite DocumentWorkflowUnlockedTest
 * - [ ] HR checklist onboarding/offboarding/milestone → HrChecklistManager (suite)
 * - [ ] Notifikasi checklist belum selesai → Notifications (suite)
 * - [ ] Export daftar dokumen dan checklist → Export (suite)
 *
 * Happy path: karyawan mengajukan permintaan dokumen → record pending.
 * Negative path: pengajuan tanpa jenis dokumen ditolak validasi.
 */
test('M5 acceptance: employee requests a document → record created', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $docType = EmployeeDocumentType::factory()->create(['name' => 'Surat Keterangan Kerja']);

    $this->actingAs($user);

    Livewire::test(EmployeeDocumentRequestPage::class)
        ->set('documentType', (string) $docType->id)
        ->set('purpose', 'Keperluan bank')
        ->set('details', 'Butuh surat keterangan kerja aktif.')
        ->call('store')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('employee_document_requests', [
        'employee_id' => $employee->id,
        'document_type_id' => $docType->id,
    ]);
});

test('M5 acceptance: document request requires a document type', function () {
    $user = User::factory()->create();
    Employee::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(EmployeeDocumentRequestPage::class)
        ->set('documentType', '')
        ->set('purpose', 'Keperluan bank')
        ->set('details', 'Tanpa jenis dokumen.')
        ->call('store')
        ->assertHasErrors(['documentType']);
});
