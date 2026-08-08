<?php

use App\Jobs\RecordQueueHeartbeat;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Urutan kronologis harian (WIB).
Schedule::command('attendance:detect-missed-clock')->dailyAt('00:15')->withoutOverlapping();
Schedule::command('maintenance:scheduled-backups')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('attendance:auto-approve-wfa')->dailyAt('06:30')->withoutOverlapping();
Schedule::command('attendance:send-reminders')->weekdays()->dailyAt('09:00')->withoutOverlapping();
Schedule::command('import-export-runs:prune-expired --hours=12')->hourly()->withoutOverlapping();
Schedule::command('attendance:detect-alpha')->dailyAt('20:00')->withoutOverlapping();
Schedule::command('attendance:detect-chronic-late')->dailyAt('23:30')->withoutOverlapping();
Schedule::command('cache:warm')->hourly()->withoutOverlapping();
Schedule::command('leave:reset-quota')->yearlyOn(1, 1, '00:10')->withoutOverlapping();
Schedule::call(fn () => Cache::put('health:scheduler_heartbeat_at', now()->toIso8601String(), now()->addMinutes(10)))
    ->name('health.scheduler-heartbeat')
    ->everyMinute();
Schedule::job(new RecordQueueHeartbeat)
    ->name('health.queue-heartbeat')
    ->everyMinute();
Schedule::command('queue:work --queue=maintenance,default --stop-when-empty --max-time=55 --tries=1')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => (bool) env('SCHEDULE_QUEUE_WORKER', true));
