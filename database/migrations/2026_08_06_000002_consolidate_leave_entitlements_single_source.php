<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M11 (AUDIT.md) — Konsolidasi dual leave quota (keputusan Fikih 2026-08-06).
 *
 * `leave_balances` = source of truth:
 *   - writer: LeaveEntitlementService (allocation), LeaveService
 *     (initializeBalance/carryForward), ApprovalService::deductLeaveQuota,
 *     Leave::deleting (refund)
 *   - reader: LeaveService, LeaveRequestService, PayrollCalculatorService
 *     (calculateLeaveCashOut — PAYROLL, garis merah), Api/LeaveController,
 *     LeaveEntitlementService (summaryFor/quotaSummariesFor/quotaErrorForRequest)
 *
 * `leave_entitlements` = turunan display yang STALE:
 *   - satu-satunya pembaca: LeaveEntitlementManager (admin)
 *   - tidak ada writer inkremental: used_days/remaining_days tidak pernah
 *     di-update setelah create (tracking usage hanya di leave_balances.used)
 *
 * Data lama DISALIN ke `leave_balances` (safety net: row balance yang belum
 * ada dibuat dari entitlement, quota = total_days), FK entitlement_id yang
 * tidak terpakai dilepas, lalu tabel legacy di-drop. Tidak ada data yang
 * dihapus. Kontrak PayrollCalculatorService TIDAK berubah (tetap
 * leave_balances).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Safety net: pastikan setiap entitlement punya balance row
        //    (service memang dual-write sinkron; jaga-jaga row balance hilang).
        $existing = DB::table('leave_balances')
            ->select('employee_id', 'leave_type_id', 'year')
            ->get()
            ->map(fn ($row) => $row->employee_id.'|'.$row->leave_type_id.'|'.$row->year)
            ->flip();

        $entitlements = DB::table('leave_entitlements')
            ->orderBy('id')
            ->get(['employee_id', 'leave_type_id', 'year', 'total_days']);

        $now = now();
        $inserts = [];

        foreach ($entitlements as $entitlement) {
            $key = $entitlement->employee_id.'|'.$entitlement->leave_type_id.'|'.$entitlement->year;

            if (isset($existing[$key])) {
                continue;
            }

            $existing[$key] = true;

            $inserts[] = [
                'employee_id' => $entitlement->employee_id,
                'leave_type_id' => $entitlement->leave_type_id,
                'year' => $entitlement->year,
                'quota' => $entitlement->total_days,
                'used' => 0,
                'carry_forward' => 0,
                'carry_forward_deadline' => null,
                'carried_forward' => 0,
                'expired_at' => null,
                'is_frozen' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('leave_balances')->insert($chunk);
        }

        // 2) Lepas FK + kolom entitlement_id (nullable, 0 konsumen) dan drop
        //    tabel legacy.
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropForeign(['entitlement_id']);
            $table->dropColumn('entitlement_id');
        });

        Schema::dropIfExists('leave_entitlements');
    }

    public function down(): void
    {
        Schema::create('leave_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('total_days', 5, 1);
            $table->decimal('used_days', 5, 1)->default(0);
            $table->decimal('remaining_days', 5, 1);
            $table->year('year');
            $table->timestamps();
        });

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->foreignId('entitlement_id')->nullable()->constrained('leave_entitlements');
        });

        // Best-effort rollback: salin kembali balance → entitlement
        // (total = quota + carry_forward; used → used_days) + re-link FK.
        $balances = DB::table('leave_balances')->get();

        foreach ($balances as $balance) {
            $total = (float) $balance->quota + (float) $balance->carry_forward;

            $id = DB::table('leave_entitlements')->insertGetId([
                'leave_type_id' => $balance->leave_type_id,
                'employee_id' => $balance->employee_id,
                'total_days' => $total,
                'used_days' => $balance->used,
                'remaining_days' => max(0, $total - (float) $balance->used),
                'year' => $balance->year,
                'created_at' => $balance->created_at,
                'updated_at' => $balance->updated_at,
            ]);

            DB::table('leave_balances')
                ->where('id', $balance->id)
                ->update(['entitlement_id' => $id]);
        }
    }
};
