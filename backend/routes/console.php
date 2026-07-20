<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks (Phase 5)
|--------------------------------------------------------------------------
| Requires a single system cron entry:
|   * * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
*/

// Remind registrants of events in the next 24h — hourly.
Schedule::command('events:send-reminders --hours=24')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

// Thank attendees of finished events + mark them completed — daily at 09:00.
Schedule::command('events:send-thank-you')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer();

// Prune old failed jobs weekly.
Schedule::command('queue:prune-failed --hours=168')->weekly();
