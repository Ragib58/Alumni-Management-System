<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \Illuminate\Notifications\DatabaseNotification $resource
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = (array) $this->data;

        return [
            'id'         => $this->id,
            'type'       => $data['type'] ?? class_basename($this->type),
            'title'      => $data['title'] ?? 'Notification',
            'message'    => $data['message'] ?? '',
            'url'        => $data['url'] ?? null,
            'data'       => $data,
            'read'       => ! is_null($this->read_at),
            'read_at'    => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
