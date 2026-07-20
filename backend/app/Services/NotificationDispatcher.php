<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Notifications\EventReminderNotification;
use App\Notifications\EventUpdatedNotification;
use App\Notifications\PaymentSuccessNotification;
use App\Notifications\RegistrationConfirmedNotification;
use App\Notifications\ThankYouNotification;
use App\Models\Payment;
use Illuminate\Support\Facades\Notification;

/**
 * Central place that fans notifications out to the right recipients. Keeps the
 * services/commands thin and the notification wiring in one spot.
 */
class NotificationDispatcher
{
    public function registrationConfirmed(EventRegistration $registration): void
    {
        $user = $registration->user ?? User::find($registration->user_id);
        $user?->notify(new RegistrationConfirmedNotification($registration));
    }

    public function paymentSuccess(Payment $payment): void
    {
        $user = $payment->registration?->user;
        $user?->notify(new PaymentSuccessNotification($payment));
    }

    /**
     * Notify everyone with an active (non-cancelled) registration for an event.
     *
     * @param array<int, string> $changes
     */
    public function eventUpdated(Event $event, array $changes = []): int
    {
        $count = 0;

        $this->activeRegistrantsChunked($event, function ($users) use ($event, $changes, &$count) {
            Notification::send($users, new EventUpdatedNotification($event, $changes));
            $count += $users->count();
        });

        return $count;
    }

    public function eventReminder(Event $event): int
    {
        $count = 0;

        $this->activeRegistrantsChunked($event, function ($users) use ($event, &$count) {
            Notification::send($users, new EventReminderNotification($event));
            $count += $users->count();
        });

        return $count;
    }

    public function thankYou(Event $event): int
    {
        $count = 0;

        // Only attendees get the thank-you.
        EventRegistration::with('user')
            ->where('event_id', $event->id)
            ->whereHas('attendance', fn ($q) => $q->attended())
            ->chunkById(500, function ($registrations) use ($event, &$count) {
                $users = $registrations->pluck('user')->filter();
                if ($users->isNotEmpty()) {
                    Notification::send($users, new ThankYouNotification($event));
                    $count += $users->count();
                }
            });

        return $count;
    }

    /**
     * Stream active registrants in chunks to stay memory-safe at 50k+ scale.
     */
    private function activeRegistrantsChunked(Event $event, callable $callback): void
    {
        EventRegistration::with('user')
            ->where('event_id', $event->id)
            ->where('status', '!=', 'cancelled')
            ->chunkById(500, function ($registrations) use ($callback) {
                $users = $registrations->pluck('user')->filter();
                if ($users->isNotEmpty()) {
                    $callback($users);
                }
            });
    }
}
