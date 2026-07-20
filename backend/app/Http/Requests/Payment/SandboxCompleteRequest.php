<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Completes a sandbox payment from the simulated gateway page.
 */
class SandboxCompleteRequest extends FormRequest
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
            'token'   => ['required', 'string'],
            'outcome' => ['required', Rule::in(['success', 'failed'])],
        ];
    }
}
