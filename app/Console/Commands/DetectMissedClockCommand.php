<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use Illuminate\Console\Command;

class DetectMissedClockCommand extends Command
{
    protected $signature = 'attendance:detect-missed-clock';

    protected $description = 'Mark attendances with missed clock-in or clock-out from previous day';

    public function handle(): int
    {
        $yesterday = now()->subDay()->toDateString();

        $missedOut = Attendance::whereDate('date', $yesterday)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->update(['status' => AttendanceStatus::MISSED_CLOCK_OUT]);

        $missedIn = Attendance::whereDate('date', $yesterday)
            ->whereNull('clock_in')
            ->whereNotNull('clock_out')
            ->update(['status' => AttendanceStatus::MISSED_CLOCK_IN]);

        $this->info("Marked {$missedOut} missed clock-out, {$missedIn} missed clock-in");
        logger()->info('attendance:detect-missed-clock completed', [
            'missed_clock_out' => $missedOut,
            'missed_clock_in' => $missedIn,
        ]);

        return Command::SUCCESS;
    }
}
