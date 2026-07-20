<?php

namespace App\Jobs;

use App\Models\EventRegistration;
use App\Services\TicketService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generates the PDF ticket (+ QR) for a confirmed registration, then queues the
 * ticket email. Idempotent — safe to retry.
 */
class GenerateTicketJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $registrationId)
    {
    }

    public function handle(TicketService $tickets): void
    {
        $registration = EventRegistration::with(['event', 'user'])->find($this->registrationId);

        if (! $registration) {
            Log::warning('GenerateTicketJob: registration not found', ['id' => $this->registrationId]);
            return;
        }

        $ticket = $tickets->generateFor($registration);

        // Hand off emailing to its own job so a mail failure doesn't lose the ticket.
        SendTicketEmailJob::dispatch($ticket->id);
    }
}
