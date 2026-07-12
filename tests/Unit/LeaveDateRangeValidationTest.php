<?php

use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Services\ApprovalService;
use App\Services\LeaveService;

/**
 * B3.7 fix verification — applyLeave() reject end_date < start_date.
 *
 * Sebelum fix: range terbalik bisa lolos sampai countWorkingDays() / Leave::hasOverlap()
 * dan menghasilkan totalDays = 0 atau perilaku undefined.
 *
 * Setelah fix: validasi range tanggal terjadi di awal method, sebelum query DB apapun.
 * Test ini exercise validasi tersebut tanpa perlu factory DB lengkap.
 */
test('applyLeave reject saat end_date sebelum start_date', function () {
    $approvalService = mock(ApprovalService::class);
    $service = new LeaveService($approvalService);

    $employee = new Employee;

    expect(fn () => $service->applyLeave($employee, [
        'leave_type_id' => 1,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-05',
        'day_type' => 'full_day',
        'reason' => 'test',
    ]))
        ->toThrow(BusinessRuleException::class, 'Tanggal akhir cuti tidak boleh lebih awal dari tanggal mulai.');
});

test('applyLeave reject saat end_date jauh sebelum start_date', function () {
    $approvalService = mock(ApprovalService::class);
    $service = new LeaveService($approvalService);

    $employee = new Employee;

    expect(fn () => $service->applyLeave($employee, [
        'leave_type_id' => 1,
        'start_date' => '2026-12-25',
        'end_date' => '2026-01-01',
        'day_type' => 'full_day',
        'reason' => 'test',
    ]))
        ->toThrow(BusinessRuleException::class);
});
