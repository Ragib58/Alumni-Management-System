<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Attendance $resource
 */
class AttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'registration_id' => $this->registration_id,
            'event_id'        => $this->event_id,
            'status'          => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'status_label'    => $this->status?->label(),
            'checkin_time'    => $this->checkin_time?->toIso8601String(),
            'checkout_time'   => $this->checkout_time?->toIso8601String(),
            'checked_by'      => $this->whenLoaded('checkedBy', fn () => [
                'id'   => $this->checkedBy?->id,
                'name' => $this->checkedBy?->name,
            ]),
            'registration'    => new EventRegistrationResource($this->whenLoaded('registration')),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
