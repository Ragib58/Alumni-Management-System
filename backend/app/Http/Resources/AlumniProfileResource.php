<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\AlumniProfile $resource
 */
class AlumniProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'user_id'           => $this->user_id,
            'student_id'        => $this->student_id,
            'batch'             => $this->batch,
            'department'        => $this->department,
            'session'           => $this->session,
            'profession'        => $this->profession,
            'company'           => $this->company,
            'designation'       => $this->designation,
            'address'           => $this->address,
            'bio'               => $this->bio,
            'profile_photo'     => $this->profile_photo,
            'profile_photo_url' => $this->profile_photo_url,
            'user'              => new UserSummaryResource($this->whenLoaded('user')),
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }
}
