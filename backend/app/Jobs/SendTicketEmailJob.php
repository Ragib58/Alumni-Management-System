<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTicketEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public int $ticketId)
    {
    }

    public function handle(TicketService $tickets): void
    {
        $ticket = Ticket::find($this->ticketId);

        if (! $ticket) {
            Log::warning('SendTicketEmailJob: ticket not found', ['id' => $this->ticketId]);
            return;
        }

        $tickets->email($ticket);
    }
}
