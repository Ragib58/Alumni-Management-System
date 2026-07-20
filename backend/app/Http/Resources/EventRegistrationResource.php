<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\EventRegistration $resource
 */
class EventRegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'registration_no'     => $this->registration_no,
            'event_id'            => $this->event_id,
            'user_id'             => $this->user_id,
            'status'              => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'status_label'        => $this->status?->label(),
            'payment_status'      => $this->payment_status instanceof \BackedEnum ? $this->payment_status->value : $this->payment_status,
            'payment_status_label' => $this->payment_status?->label(),
            'amount'              => (float) $this->amount,
            'form_response'       => $this->form_response ?? [],
            'registered_at'       => $this->registered_at?->toIso8601String(),
            'cancelled_at'        => $this->cancelled_at?->toIso8601String(),

            'event'               => new EventResource($this->whenLoaded('event')),
            'user'                => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ]),

            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }
}
