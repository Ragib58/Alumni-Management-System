<?php

namespace App\Notifications;

use App\Models\Event;
use App\Notifications\Messages\SmsMessage;
use Illuminate\Notifications\Messages\MailMessage;

class EventUpdatedNotification extends BaseNotification
{
    /**
     * @param array<int, string> $changes human-readable list of what changed
     */
    public function __construct(public Event $event, public array $changes = [])
    {
    }

    protected function defaultChannels(): array
    {
        return ['mail', 'database', 'sms'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Update: '.$this->event->title)
            ->greeting('Hi '.$notifiable->name.',')
            ->line('There has been an update to **'.$this->event->title.'** which you are registered for.');

        foreach ($this->changes as $change) {
            $mail->line('• '.$change);
        }

        return $mail
            ->action('View event', rtrim(config('app.frontend_url'), '/').'/events/'.$this->event->slug)
            ->line('Please review the latest details.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'     => 'event_updated',
            'title'    => 'Event updated',
            'message'  => $this->event->title.' has been updated.',
            'event_id' => $this->event->id,
            'changes'  => $this->changes,
            'url'      => '/events/'.$this->event->slug,
        ];
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return SmsMessage::create($this->event->title.' has been updated. Check the app for details.');
    }
}
