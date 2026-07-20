<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Sponsor $resource
 */
class SponsorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'event_id'          => $this->event_id,
            'name'              => $this->name,
            'logo'              => $this->logo,
            'logo_url'          => $this->logo_url,
            'website'           => $this->website,
            'amount'            => (float) $this->amount,
            'sponsor_type'      => $this->sponsor_type instanceof \BackedEnum ? $this->sponsor_type->value : $this->sponsor_type,
            'sponsor_type_label' => $this->sponsor_type?->label(),
            'sort_order'        => (int) $this->sort_order,
            'is_active'         => (bool) $this->is_active,
            'event'             => $this->whenLoaded('event', fn () => [
                'id'    => $this->event?->id,
                'title' => $this->event?->title,
            ]),
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
