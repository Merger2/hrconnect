<?php

use App\Jobs\RecordQueueHeartbeat;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('maintenance:scheduled-backups')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('import-export-runs:prune-expired --hours=12')->hourly()->withoutOverlapping();
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
