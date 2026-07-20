<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Wraps the dynamic-form submission. Per-field validation is performed in
 * RegistrationService against the event's configured form fields, so here we
 * only assert the envelope shape.
 */
class RegisterEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Allow the answers to arrive as a JSON string (multipart with files).
        if (is_string($this->form_response)) {
            $decoded = json_decode($this->form_response, true);
            $this->merge(['form_response' => is_array($decoded) ? $decoded : []]);
        }

        if (is_null($this->form_response)) {
            $this->merge(['form_response' => []]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'form_response' => ['array'],
        ];
    }
}
