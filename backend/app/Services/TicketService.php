<?php

namespace App\Services;

use App\Mail\TicketMail;
use App\Models\EventRegistration;
use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketService
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
        private readonly QrService $qr,
    ) {
    }

    /**
     * Idempotently create the ticket + QR for a paid/confirmed registration and
     * render its PDF. Safe to call repeatedly (e.g. retried queue job).
     */
    public function generateFor(EventRegistration $registration): Ticket
    {
        $ticket = $this->tickets->findByRegistrationId($registration->id);

        if (! $ticket) {
            $token = $this->qr->generateToken();

            /** @var Ticket $ticket */
            $ticket = $this->tickets->create([
                'registration_id' => $registration->id,
                'ticket_no'       => $this->generateTicketNo(),
                'qr_token'        => $token,
                'qr_signature'    => $this->qr->sign($token, $registration),
                'issued_at'       => Carbon::now(),
            ]);
        }

        // (Re)render the PDF and persist its path.
        $path = $this->renderPdf($ticket->fresh(['registration.event', 'registration.user']) ?? $ticket, $registration);
        $this->tickets->update($ticket, ['pdf_path' => $path]);

        return $ticket->fresh(['registration.event', 'registration.user']);
    }

    /**
     * Render (or re-render) the ticket PDF to the public disk and return its path.
     */
    public function renderPdf(Ticket $ticket, ?EventRegistration $registration = null): string
    {
        $registration = $registration
            ?? $ticket->registration()->with(['event', 'user'])->first();

        $qrDataUri = $this->qr->svgDataUri($ticket->qr_token, $registration);

        $pdf = Pdf::loadView('tickets.ticket', [
            'ticket'       => $ticket,
            'registration' => $registration,
            'event'        => $registration->event,
            'participant'  => $registration->user,
            'qr'           => $qrDataUri,
        ])->setPaper('a5', 'landscape');

        $path = 'tickets/'.$ticket->ticket_no.'.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Ensure a fresh PDF exists on disk and return its absolute filesystem path
     * (used for streamed downloads).
     */
    public function ensurePdfPath(Ticket $ticket): string
    {
        if (! $ticket->pdf_path || ! Storage::disk('public')->exists($ticket->pdf_path)) {
            $path = $this->renderPdf($ticket);
            $this->tickets->update($ticket, ['pdf_path' => $path]);
            $ticket->pdf_path = $path;
        }

        return Storage::disk('public')->path($ticket->pdf_path);
    }

    /**
     * Email the ticket (with the PDF attached) to the participant.
     */
    public function email(Ticket $ticket): void
    {
        $registration = $ticket->registration()->with(['event', 'user'])->first();
        $user = $registration?->user;

        if (! $user?->email) {
            return;
        }

        $pdfPath = $this->ensurePdfPath($ticket);

        Mail::to($user->email)->send(new TicketMail($ticket, $registration, $pdfPath));

        $this->tickets->update($ticket, ['emailed_at' => Carbon::now()]);
    }

    private function generateTicketNo(): string
    {
        do {
            $no = 'TKT-'.Carbon::now()->format('Y').'-'.strtoupper(Str::random(6));
        } while (Ticket::where('ticket_no', $no)->exists());

        return $no;
    }
}
