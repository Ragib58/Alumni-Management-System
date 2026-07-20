<?php

namespace App\Notifications;

use App\Models\Event;
use App\Notifications\Messages\SmsMessage;
use Illuminate\Notifications\Messages\MailMessage;

class EventReminderNotification extends BaseNotification
{
    public function __construct(public Event $event)
    {
    }

    protected function defaultChannels(): array
    {
        return ['mail', 'database', 'sms'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reminder: '.$this->event->title.' is coming up')
            ->greeting('Hi '.$notifiable->name.',')
            ->line('This is a friendly reminder that **'.$this->event->title.'** is on '
                .optional($this->event->event_date)->format('D, d M Y \a\t h:i A').'.')
            ->when($this->event->venue, fn ($m) => $m->line('Venue: '.$this->event->venue))
            ->action('View event', rtrim(config('app.frontend_url'), '/').'/events/'.$this->event->slug)
            ->line('See you there!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'     => 'event_reminder',
            'title'    => 'Event reminder',
            'message'  => $this->event->title.' is on '.optional($this->event->event_date)->format('d M Y, h:i A').'.',
            'event_id' => $this->event->id,
            'url'      => '/events/'.$this->event->slug,
        ];
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return SmsMessage::create(
            'Reminder: '.$this->event->title.' on '
            .optional($this->event->event_date)->format('d M, h:i A').'.'
        );
    }
}
