<?php

namespace App\Http\Requests\Sponsor;

use App\Enums\SponsorType;
use App\Models\Sponsor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSponsorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Sponsor::class) ?? false;
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
            'name'         => ['required', 'string', 'max:150'],
            'website'      => ['nullable', 'url', 'max:255'],
            'amount'       => ['nullable', 'numeric', 'min:0'],
            'sponsor_type' => ['required', Rule::in(SponsorType::values())],
            'sort_order'   => ['nullable', 'integer', 'min:0'],
            'is_active'    => ['boolean'],
            'logo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ];
    }
}
