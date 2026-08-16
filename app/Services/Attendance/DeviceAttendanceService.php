<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class DeviceAttendanceService
{
    /**
     * Lampirkan foto absensi ke record attendance hari ini (slot in/out).
     *
     * Mock-miss fix (2026-08-16): sebelumnya stub — selalu return
     * `attendance->id = 0` tanpa menyimpan apa pun (foto dibuang, sukses
     * palsu). Kini foto disimpan ke disk local dan di-link ke attendance
     * hari ini via kolom photo_selfie_in/out. Policy face-only dihormati:
     * service TIDAK membuat record attendance — hanya melampirkan bukti
     * foto ke record yang sudah ada (dibuat via ClockInAction/offline sync).
     *
     * @param  UploadedFile|null  $photo
     * @return array{attendance: Attendance, slot: 'in'|'out'}
     *
     * @throws BusinessRuleException
     */
    public function uploadPhoto(int|string $userId, $photo, ?float $latitude = null, ?float $longitude = null): array
    {
        $user = User::query()->find($userId);
        $employee = $user?->employee;

        if (! $employee) {
            throw new BusinessRuleException('Akun tidak terhubung dengan data karyawan.');
        }

        if (! $photo instanceof UploadedFile) {
            throw new BusinessRuleException('Foto absensi tidak valid.');
        }

        $attendance = $employee->getTodayActiveAttendance()
            ?? Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', now()->toDateString())
                ->latest('id')
                ->first();

        if (! $attendance) {
            throw new BusinessRuleException('Tidak ada catatan absensi hari ini untuk melampirkan foto.');
        }

        $slot = blank($attendance->photo_selfie_in) ? 'in' : 'out';
        $column = $slot === 'in' ? 'photo_selfie_in' : 'photo_selfie_out';

        $path = $photo->store('attendance_photos/'.now()->format('Y/m/d'), 'local');

        $attendance->update([$column => $path]);

        return [
            'attendance' => $attendance,
            'slot' => $slot,
        ];
    }
}
