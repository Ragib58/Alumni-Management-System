<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\SmsMessage;
use App\Services\SmsService;
use Illuminate\Notifications\Notification;

/**
 * Custom Laravel notification channel that routes `toSms()` through SmsService.
 * Register the alias 'sms' → this class in a service provider.
 */
class SmsChannel
{
    public function __construct(private readonly SmsService $sms)
    {
    }

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $to = $notifiable->routeNotificationFor('sms', $notification)
            ?? $notifiable->phone
            ?? null;

        if (! $to) {
            return;
        }

        /** @var SmsMessage|string $message */
        $message = $notification->toSms($notifiable);
        $content = $message instanceof SmsMessage ? $message->content : (string) $message;
        $from = $message instanceof SmsMessage ? $message->from : null;

        $this->sms->send($to, $content, $from);
    }
}
