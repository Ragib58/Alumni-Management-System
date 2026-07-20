<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Notifications\Messages\SmsMessage;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentSuccessNotification extends BaseNotification
{
    public function __construct(public Payment $payment)
    {
    }

    protected function defaultChannels(): array
    {
        return ['mail', 'database', 'sms'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $event = $this->payment->registration?->event;

        return (new MailMessage)
            ->subject('Payment Received — '.($event?->title ?? 'Event'))
            ->greeting('Hi '.$notifiable->name.',')
            ->line('We have received your payment of '.number_format((float) $this->payment->amount, 2).' '.$this->payment->currency.'.')
            ->line('Transaction ID: '.$this->payment->transaction_id)
            ->action('View my tickets', rtrim(config('app.frontend_url'), '/').'/my-tickets')
            ->line('Your ticket has been generated and emailed to you.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'           => 'payment_success',
            'title'          => 'Payment successful',
            'message'        => 'Your payment of '.number_format((float) $this->payment->amount, 2).' '.$this->payment->currency.' was received.',
            'transaction_id' => $this->payment->transaction_id,
            'url'            => '/my-tickets',
        ];
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return SmsMessage::create(
            'Payment of '.number_format((float) $this->payment->amount, 2).' '.$this->payment->currency
            .' received. Txn: '.$this->payment->transaction_id
        );
    }
}
