<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Cron schedule untuk command HRConnect. Pastikan crontab di server jalan:
|   * * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
|
| Verifikasi: php artisan schedule:list
*/

// Alpha detection: setiap hari jam 23:59
// Skip otomatis kalau weekend / holiday (built-in di command).
Schedule::command('attendance:detect-alpha')
    ->dailyAt('23:59')
    ->withoutOverlapping()
    ->environments(['production'])
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/schedule.log'))
    ->onSuccess(fn () => logger()->info('attendance:detect-alpha selesai'))
    ->onFailure(fn () => logger()->error('attendance:detect-alpha gagal'));

// Chronic late warning: setiap Jumat jam 18:00
Schedule::command('attendance:detect-chronic-late')
    ->weeklyOn(Illuminate\Console\Scheduling\Schedule::FRIDAY, '18:00')
    ->withoutOverlapping()
    ->environments(['production'])
    ->appendOutputTo(storage_path('logs/schedule.log'));

// Reset leave quota: 1 Januari 00:00 setiap tahun
Schedule::command('leave:reset-quota')
    ->yearlyOn(1, 1, '00:00')
    ->withoutOverlapping()
    ->environments(['production'])
    ->appendOutputTo(storage_path('logs/schedule.log'))
    ->onFailure(fn () => logger()->error('leave:reset-quota gagal'));

// Cache warm: setiap hari jam 05:00 (sebelum jam kerja)
Schedule::command('cache:warm')
    ->dailyAt('05:00')
    ->withoutOverlapping()
    ->environments(['production'])
    ->appendOutputTo(storage_path('logs/schedule.log'));

// WFA auto-approve: setiap hari jam 02:00 (cek WFA pending > 3 hari kerja)
Schedule::command('attendance:auto-approve-wfa')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->environments(['production'])
    ->appendOutputTo(storage_path('logs/schedule.log'))
    ->onSuccess(fn () => logger()->info('attendance:auto-approve-wfa selesai'))
    ->onFailure(fn () => logger()->error('attendance:auto-approve-wfa gagal'));

// payroll:generate TIDAK auto-scheduled — manual trigger via Finance UI / artisan.

// B-5: Missed clock detection: setiap hari jam 00:01 (deteksi hari sebelumnya)
Schedule::command('attendance:detect-missed-clock')
    ->dailyAt('00:01')
    ->withoutOverlapping()
    ->environments(['production'])
    ->appendOutputTo(storage_path('logs/schedule.log'))
    ->onSuccess(fn () => logger()->info('attendance:detect-missed-clock selesai'))
    ->onFailure(fn () => logger()->error('attendance:detect-missed-clock gagal'));

// Attendance reminder: setiap jam kerja jam 09:00
Schedule::command('attendance:send-reminders')
    ->weekdays()
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->environments(['production'])
    ->appendOutputTo(storage_path('logs/schedule.log'))
    ->onSuccess(fn () => logger()->info('attendance:send-reminders selesai'))
    ->onFailure(fn () => logger()->error('attendance:send-reminders gagal'));

// Backup: setiap hari jam 01:00 (setelah auto-approve WFA, sebelum cache warm)
Schedule::command('backup:clean')
    ->dailyAt('01:00')
    ->environments(['production'])
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/schedule.log'))
    ->onSuccess(fn () => logger()->info('backup:clean selesai'))
    ->onFailure(fn () => logger()->error('backup:clean gagal'));

// Backup: setiap hari jam 01:30 (DB dump + files via tar.gz — workaround ZipArchive 1.11.4/PHP 8.5 bug)
Schedule::command('hrconnect:backup')
    ->dailyAt('01:30')
    ->environments(['production'])
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule.log'))
    ->onSuccess(fn () => logger()->info('hrconnect:backup selesai'))
    ->onFailure(fn () => logger()->error('hrconnect:backup gagal'));
