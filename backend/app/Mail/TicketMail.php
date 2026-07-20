<?php

namespace App\Mail;

use App\Models\EventRegistration;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public EventRegistration $registration,
        public ?string $pdfPath = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Ticket — '.($this->registration->event?->title ?? 'Event'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket',
            with: [
                'ticket'       => $this->ticket,
                'registration' => $this->registration,
                'event'        => $this->registration->event,
                'participant'  => $this->registration->user,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->pdfPath) {
            return [];
        }

        return [
            Attachment::fromPath($this->pdfPath)
                ->as($this->ticket->ticket_no.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
