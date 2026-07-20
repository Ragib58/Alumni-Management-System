<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Services\NotificationDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Sends thank-you messages to attendees of events that finished in the last day
 * and marks the event completed. Intended to run daily via the scheduler.
 */
class SendThankYouMessages extends Command
{
    protected $signature = 'events:send-thank-you';

    protected $description = 'Send thank-you notifications to attendees of finished events';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $events = Event::query()
            ->whereIn('status', [EventStatus::Published->value, EventStatus::Closed->value])
            ->whereNotNull('event_date')
            ->where('event_date', '<', Carbon::now())
            ->where('event_date', '>=', Carbon::now()->subDay())
            ->get();

        $total = 0;
        foreach ($events as $event) {
            $sent = $dispatcher->thankYou($event);
            $total += $sent;

            // Mark the event completed once thank-yous have gone out.
            $event->forceFill(['status' => EventStatus::Completed->value])->save();

            $this->info("Thank-you queued for '{$event->title}' → {$sent} attendees.");
        }

        $this->info("Done. {$total} thank-you notifications queued.");

        return self::SUCCESS;
    }
}
