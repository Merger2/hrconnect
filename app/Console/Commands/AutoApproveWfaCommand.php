<?php

namespace App\Console\Commands;

use App\Enums\WfaStatus;
use App\Models\Attendance;
use App\Models\CompanySetting;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoApproveWfaCommand extends Command
{
    protected $signature = 'attendance:auto-approve-wfa
                            {--date= : Tanggal referensi (YYYY-MM-DD), default hari ini}';

    protected $description = 'Auto-approve WFA yang melebihi batas waktu approve (default 3 hari kerja)';

    public function handle(): int
    {
        $referenceDate = $this->option('date')
            ? CarbonImmutable::parse($this->option('date'))
            : now();

        $timeoutDays = (int) CompanySetting::get('wfa_auto_approve_days', 3);

        $cutoffDate = $referenceDate->copy()->subWeekdays($timeoutDays)->startOfDay();

        $pendingAttendances = Attendance::query()
            ->where('is_wfa', true)
            ->where('status_wfa', WfaStatus::PENDING->value)
            ->whereDate('date', '<=', $cutoffDate->toDateString())
            ->get();

        $bar = $this->output->createProgressBar($pendingAttendances->count());
        $bar->start();

        $approvedCount = 0;
        foreach ($pendingAttendances as $attendance) {
            DB::transaction(function () use ($attendance, $timeoutDays) {
                $locked = Attendance::lockForUpdate()->find($attendance->id);

                if (! $locked || $locked->status_wfa !== WfaStatus::PENDING) {
                    return;
                }

                $locked->update([
                    'status_wfa' => WfaStatus::APPROVED,
                ]);

                activity()
                    ->performedOn($locked)
                    ->withProperties([
                        'auto_approved' => true,
                        'timeout_days' => $timeoutDays,
                    ])
                    ->log('WFA otomatis di-approve setelah melebihi batas waktu');
            });

            $approvedCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Selesai. {$approvedCount} WFA di-auto-approve (cutoff: {$cutoffDate->toDateString()}).");

        return self::SUCCESS;
    }
}
