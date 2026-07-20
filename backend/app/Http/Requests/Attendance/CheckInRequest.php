<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Accepts either a scanned QR value (`qr` — raw payload or bare token) or a
 * `registration_id` for manual check-in. An optional `event_id` binds the
 * scanner to a specific event.
 */
class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'qr'              => ['required_without:registration_id', 'nullable', 'string', 'max:2000'],
            'registration_id' => ['required_without:qr', 'nullable', 'integer'],
            'event_id'        => ['nullable', 'integer'],
        ];
    }
}
