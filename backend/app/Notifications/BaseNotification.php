<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Base for all event notifications. Resolves the delivery channels from the
 * notifiable's `notification_preferences` (falling back to sensible defaults),
 * and is queued so sending never blocks the request.
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Channels this notification would use if the user hasn't opted out.
     *
     * @return array<int, string>  e.g. ['mail', 'database', 'sms']
     */
    abstract protected function defaultChannels(): array;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $prefs = (array) ($notifiable->notification_preferences ?? []);
        $map = ['mail' => 'mail', 'database' => 'database', 'sms' => SmsChannel::class];

        $channels = [];
        foreach ($this->defaultChannels() as $channel) {
            if ($channel === 'sms') {
                // SMS is opt-in and needs a phone number.
                $enabled = (bool) ($prefs['sms'] ?? false) && ! empty($notifiable->phone);
            } else {
                // Email + in-app are on by default unless explicitly disabled.
                $enabled = (bool) ($prefs[$channel] ?? true);
            }

            if ($enabled && isset($map[$channel])) {
                $channels[] = $map[$channel];
            }
        }

        // Always keep at least the in-app record.
        return $channels ?: ['database'];
    }
}
