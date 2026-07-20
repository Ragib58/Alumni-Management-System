<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\ActivityLog $resource
 */
class ActivityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'action'       => $this->action instanceof \BackedEnum ? $this->action->value : $this->action,
            'action_label' => $this->action?->label(),
            'description'  => $this->description,
            'subject_type' => $this->subject_type ? class_basename($this->subject_type) : null,
            'subject_id'   => $this->subject_id,
            'properties'   => $this->properties,
            'ip_address'   => $this->ip_address,
            'user'         => $this->whenLoaded('user', fn () => [
                'id'    => $this->user?->id,
                'name'  => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }
}
