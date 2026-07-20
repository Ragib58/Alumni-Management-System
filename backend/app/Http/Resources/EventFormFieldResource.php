<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\EventFormField $resource
 */
class EventFormFieldResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'event_id'    => $this->event_id,
            'label'       => $this->label,
            'name'        => $this->name,
            'type'        => $this->type instanceof \BackedEnum ? $this->type->value : $this->type,
            'options'     => $this->options ?? [],
            'is_required' => (bool) $this->is_required,
            'placeholder' => $this->placeholder,
            'help_text'   => $this->help_text,
            'sort_order'  => (int) $this->sort_order,
        ];
    }
}
