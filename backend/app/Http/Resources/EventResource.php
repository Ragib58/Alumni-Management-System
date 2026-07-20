<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Event $resource
 */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'slug'                 => $this->slug,
            'banner'               => $this->banner,
            'banner_url'           => $this->banner_url,
            'description'          => $this->description,
            'venue'                => $this->venue,
            'type'                 => $this->type instanceof \BackedEnum ? $this->type->value : $this->type,
            'type_label'           => $this->type?->label(),
            'event_date'           => $this->event_date?->toIso8601String(),
            'registration_start'   => $this->registration_start?->toIso8601String(),
            'registration_end'     => $this->registration_end?->toIso8601String(),
            'fee'                  => (float) $this->fee,
            'is_paid'              => (float) $this->fee > 0,
            'max_capacity'         => $this->max_capacity,
            'status'               => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'status_label'         => $this->status?->label(),

            // Capacity / availability (computed attributes on the model)
            'confirmed_count'      => $this->confirmed_count,
            'seats_left'           => $this->seats_left,
            'is_full'              => $this->is_full,
            'is_registration_open' => $this->is_registration_open,

            'created_by'           => $this->whenLoaded('creator', fn () => [
                'id'   => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
            'form_fields'          => EventFormFieldResource::collection($this->whenLoaded('formFields')),
            'sponsors'             => SponsorResource::collection($this->whenLoaded('sponsors')),

            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
