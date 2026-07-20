<?php

namespace App\Notifications;

use App\Models\EventRegistration;
use App\Notifications\Messages\SmsMessage;
use Illuminate\Notifications\Messages\MailMessage;

class RegistrationConfirmedNotification extends BaseNotification
{
    public function __construct(public EventRegistration $registration)
    {
    }

    protected function defaultChannels(): array
    {
        return ['mail', 'database', 'sms'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $event = $this->registration->event;

        return (new MailMessage)
            ->subject('Registration Confirmed — '.($event?->title ?? 'Event'))
            ->greeting('Hi '.$notifiable->name.',')
            ->line('Your registration for **'.($event?->title ?? 'the event').'** is confirmed.')
            ->line('Registration No: '.$this->registration->registration_no)
            ->action('View my registrations', rtrim(config('app.frontend_url'), '/').'/my-registrations')
            ->line('We look forward to seeing you there!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'registration_confirmed',
            'title'          => 'Registration confirmed',
            'message'        => 'You are registered for '.($this->registration->event?->title ?? 'an event').'.',
            'event_id'       => $this->registration->event_id,
            'registration_no' => $this->registration->registration_no,
            'url'            => '/my-registrations',
        ];
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return SmsMessage::create(
            'Your registration ('.$this->registration->registration_no.') for '
            .($this->registration->event?->title ?? 'the event').' is confirmed.'
        );
    }
}
