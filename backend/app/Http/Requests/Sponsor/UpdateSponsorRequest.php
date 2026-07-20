<?php

namespace App\Http\Requests\Sponsor;

use App\Enums\SponsorType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSponsorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorized via policy in the controller
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_id'     => ['nullable', 'integer', 'exists:events,id'],
            'name'         => ['sometimes', 'required', 'string', 'max:150'],
            'website'      => ['nullable', 'url', 'max:255'],
            'amount'       => ['nullable', 'numeric', 'min:0'],
            'sponsor_type' => ['sometimes', 'required', Rule::in(SponsorType::values())],
            'sort_order'   => ['nullable', 'integer', 'min:0'],
            'is_active'    => ['boolean'],
            'logo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ];
    }
}
