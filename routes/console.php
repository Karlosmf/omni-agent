<?php

use App\Support\CronHeartbeat;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:sync-currency-rates')->twiceDaily(10, 15);
Schedule::command('omni:notify-pending')->dailyAt('09:00');

// Shared hosting has no permanent worker: the single per-minute cron runs the scheduler,
// and the scheduler drains the queue (see config/omni-hosting.php).
Schedule::call(fn () => CronHeartbeat::beat())->name('cron-heartbeat')->everyMinute();
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->name('process-queue')
    ->everyMinute()
    ->withoutOverlapping(5);
