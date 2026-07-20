<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Payment $resource
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'registration_id'        => $this->registration_id,
            'transaction_id'         => $this->transaction_id,
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'amount'                 => (float) $this->amount,
            'currency'               => $this->currency,
            'gateway'                => $this->gateway instanceof \BackedEnum ? $this->gateway->value : $this->gateway,
            'gateway_label'          => $this->gateway?->label(),
            'status'                 => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'status_label'           => $this->status?->label(),
            'payment_date'           => $this->payment_date?->toIso8601String(),
            'registration'           => new EventRegistrationResource($this->whenLoaded('registration')),
            'created_at'             => $this->created_at?->toIso8601String(),
        ];
    }
}
