<?php

namespace App\Http\Requests\Event;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegistrationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized in the controller via policy.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status'         => ['required', Rule::in(RegistrationStatus::values())],
            'payment_status' => ['nullable', Rule::in(PaymentStatus::values())],
        ];
    }
}
