<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Notifications\AttendanceReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class AttendanceSendReminders extends Command
{
    protected $signature = 'attendance:send-reminders
        {--dry-run : Tampilkan daftar tanpa mengirim notifikasi}';

    protected $description = 'Kirim reminder ke karyawan yang belum clock-in hari ini (sebelum jam 10:00)';

    public function handle(): int
    {
        $now = CarbonImmutable::now();
        $today = $now->toDateString();

        $employeesWithoutAttendance = Employee::query()
            ->where('status', 'active')
            ->whereDoesntHave('attendances', function ($q) use ($today) {
                $q->whereDate('date', $today);
            })
            ->whereHas('user')
            ->with('user')
            ->get();

        if ($employeesWithoutAttendance->isEmpty()) {
            $this->info('Semua karyawan aktif sudah clock-in hari ini.');

            return self::SUCCESS;
        }

        $this->warn("{$employeesWithoutAttendance->count()} karyawan belum clock-in hari ini.");

        if ($this->option('dry-run')) {
            $this->table(['ID', 'Nama', 'Email'], $employeesWithoutAttendance->map(fn ($e) => [
                $e->id,
                $e->full_name,
                $e->user->email,
            ]));

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($employeesWithoutAttendance->count());
        $bar->start();

        foreach ($employeesWithoutAttendance as $employee) {
            Notification::send($employee->user, new AttendanceReminder);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Notifikasi reminder berhasil dikirim.');

        return self::SUCCESS;
    }
}
