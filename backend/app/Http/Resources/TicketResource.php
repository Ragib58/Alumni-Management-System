<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Ticket $resource
 */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'registration_id' => $this->registration_id,
            'ticket_no'       => $this->ticket_no,
            'qr_token'        => $this->qr_token,
            'pdf_url'         => $this->pdf_url,
            'issued_at'       => $this->issued_at?->toIso8601String(),
            'emailed_at'      => $this->emailed_at?->toIso8601String(),
            'checked_in_at'   => $this->checked_in_at?->toIso8601String(),
            'is_checked_in'   => ! is_null($this->checked_in_at),
            'registration'    => new EventRegistrationResource($this->whenLoaded('registration')),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
