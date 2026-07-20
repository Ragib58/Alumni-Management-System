<?php

namespace App\Http\Requests\Event;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\FormFieldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized in the controller via policy on the resolved model.
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->form_fields)) {
            $decoded = json_decode($this->form_fields, true);
            $this->merge(['form_fields' => is_array($decoded) ? $decoded : []]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title'              => ['sometimes', 'required', 'string', 'max:255'],
            'description'        => ['nullable', 'string', 'max:10000'],
            'venue'              => ['nullable', 'string', 'max:255'],
            'type'               => ['sometimes', 'required', Rule::in(EventType::values())],
            'banner'             => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            'event_date'         => ['sometimes', 'required', 'date'],
            'registration_start' => ['nullable', 'date'],
            'registration_end'   => ['nullable', 'date', 'after_or_equal:registration_start'],

            'fee'                => ['nullable', 'numeric', 'min:0'],
            'max_capacity'       => ['nullable', 'integer', 'min:1'],
            'status'             => ['sometimes', 'required', Rule::in(EventStatus::values())],

            'form_fields'                 => ['nullable', 'array'],
            'form_fields.*.label'         => ['required', 'string', 'max:150'],
            'form_fields.*.name'          => ['nullable', 'string', 'max:100'],
            'form_fields.*.type'          => ['required', Rule::in(FormFieldType::values())],
            'form_fields.*.options'       => ['nullable', 'array'],
            'form_fields.*.options.*'     => ['string', 'max:150'],
            'form_fields.*.is_required'   => ['boolean'],
            'form_fields.*.placeholder'   => ['nullable', 'string', 'max:150'],
            'form_fields.*.help_text'     => ['nullable', 'string', 'max:255'],
            'form_fields.*.sort_order'    => ['nullable', 'integer', 'min:0'],
        ];
    }
}
