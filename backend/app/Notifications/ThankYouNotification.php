<?php

namespace App\Notifications;

use App\Models\Event;
use App\Notifications\Messages\SmsMessage;
use Illuminate\Notifications\Messages\MailMessage;

class ThankYouNotification extends BaseNotification
{
    public function __construct(public Event $event)
    {
    }

    protected function defaultChannels(): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Thank you for attending '.$this->event->title)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('Thank you for attending **'.$this->event->title.'**! We hope you had a great time.')
            ->line('Your participation makes our alumni community stronger.')
            ->action('Explore more events', rtrim(config('app.frontend_url'), '/').'/events')
            ->line('We hope to see you at the next one.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'     => 'thank_you',
            'title'    => 'Thank you for attending',
            'message'  => 'Thanks for attending '.$this->event->title.'!',
            'event_id' => $this->event->id,
            'url'      => '/events',
        ];
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return SmsMessage::create('Thank you for attending '.$this->event->title.'!');
    }
}
