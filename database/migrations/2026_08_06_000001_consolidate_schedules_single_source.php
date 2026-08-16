<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M11 (AUDIT.md) — Konsolidasi dual scheduling (keputusan Fikih 2026-08-06).
 *
 * `schedules` (user_id) = source of truth:
 *   - writer: ScheduleComponent (admin), ShiftSwapRequestService::approve,
 *     ShiftForm::delete
 *   - reader: ClockInAction, HomeAttendanceStatus, ShiftSchedulePage,
 *     ShiftSwapRequestPage, ShiftSwapRequestService, ScheduleComponent
 *   - FK: shift_swap_requests.schedule_id
 *
 * `shift_schedules` (employee_id) = legacy roster (2026-05-08), write-deserted:
 *   - satu-satunya pembaca: ScheduleRosterExport
 *   - tidak ada writer aplikasi (hanya delete via ShiftForm + test factory)
 *
 * Data lama DISALIN ke `schedules` (employee → user via employees.user_id),
 * konflik unique(user_id, date) di-ignore (schedules menang), lalu tabel
 * legacy di-drop. Tidak ada data yang dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('shift_schedules')
            ->join('employees', 'employees.id', '=', 'shift_schedules.employee_id')
            ->whereNotNull('employees.user_id')
            ->get([
                'employees.user_id',
                'shift_schedules.shift_id',
                'shift_schedules.date',
                'shift_schedules.created_at',
                'shift_schedules.updated_at',
            ]);

        foreach ($rows->chunk(500) as $chunk) {
            DB::table('schedules')->insertOrIgnore(
                $chunk->map(fn ($row) => [
                    'user_id' => $row->user_id,
                    'shift_id' => $row->shift_id,
                    'date' => $row->date,
                    'is_off' => false,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ])->all()
            );
        }

        Schema::dropIfExists('shift_schedules');
    }

    public function down(): void
    {
        Schema::create('shift_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->restrictOnDelete();
            $table->date('date');
            $table->timestamps();
            $table->unique(['employee_id', 'date']);
        });

        // Best-effort rollback: salin kembali jadwal yang punya shift
        // (is_off tidak punya representasi di shift_schedules → dilewatkan).
        $rows = DB::table('schedules')
            ->join('users', 'users.id', '=', 'schedules.user_id')
            ->join('employees', 'employees.user_id', '=', 'users.id')
            ->whereNotNull('schedules.shift_id')
            ->get([
                'employees.id as employee_id',
                'schedules.shift_id',
                'schedules.date',
                'schedules.created_at',
                'schedules.updated_at',
            ]);

        foreach ($rows->chunk(500) as $chunk) {
            DB::table('shift_schedules')->insertOrIgnore(
                $chunk->map(fn ($row) => [
                    'employee_id' => $row->employee_id,
                    'shift_id' => $row->shift_id,
                    'date' => $row->date,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ])->all()
            );
        }
    }
};
