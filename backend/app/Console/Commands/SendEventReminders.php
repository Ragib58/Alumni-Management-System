<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Services\NotificationDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Sends a reminder to registrants of published events happening within the next
 * 24 hours. Intended to run hourly via the scheduler.
 */
class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders {--hours=24 : Look-ahead window in hours}';

    protected $description = 'Send event reminder notifications to registrants';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $hours = (int) $this->option('hours');
        $now = Carbon::now();
        $until = (clone $now)->addHours($hours);

        $events = Event::query()
            ->where('status', EventStatus::Published->value)
            ->whereBetween('event_date', [$now, $until])
            ->get();

        $total = 0;
        foreach ($events as $event) {
            $sent = $dispatcher->eventReminder($event);
            $total += $sent;
            $this->info("Reminder queued for '{$event->title}' → {$sent} registrants.");
        }

        $this->info("Done. {$total} reminder notifications queued across {$events->count()} events.");

        return self::SUCCESS;
    }
}
