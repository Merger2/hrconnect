<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\Shift;

/**
 * Observer untuk Employee model.
 * Tugas: auto-assign default shift_id saat employee baru dibuat tanpa shift.
 *
 * Logic resolve default shift:
 * 1. Cari Shift aktif dengan nama mengandung "Office Hour"
 * 2. Kalau tidak ada, pakai Shift aktif pertama
 * 3. Kalau tidak ada Shift sama sekali, biarkan shift_id NULL
 *    (migration sudah nullable, employee tetap bisa dibuat)
 *
 * Dipasang di AppServiceProvider::boot() (Sesi 10) lewat:
 *     Employee::observe(EmployeeObserver::class);
 */
class EmployeeObserver
{
    /**
     * Triggered SEBELUM model di-INSERT.
     * Pakai `creating` (bukan `created`) supaya modifikasi shift_id
     * tersimpan saat INSERT pertama — tidak butuh extra UPDATE query.
     */
    public function creating(Employee $employee): void
    {
        if (empty($employee->shift_id)) {
            $defaultShift = Shift::where('is_active', true)
                ->where('name', 'like', '%Office Hour%')
                ->first()
                ?? Shift::where('is_active', true)->first();

            if ($defaultShift) {
                $employee->shift_id = $defaultShift->id;
            }
        }
    }
}
