<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used both by the alumni "edit my profile" flow and admin profile edits.
 */
class UpdateProfileRequest extends FormRequest
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
        // Ignore the current profile's student_id on uniqueness (admin route has {alumni}).
        $ignoreId = $this->route('alumni');

        return [
            'student_id'    => ['nullable', 'string', 'max:50', Rule::unique('alumni_profiles', 'student_id')->ignore($ignoreId)],
            'batch'         => ['nullable', 'string', 'max:20'],
            'department'    => ['nullable', 'string', 'max:100'],
            'session'       => ['nullable', 'string', 'max:20'],
            'profession'    => ['nullable', 'string', 'max:100'],
            'company'       => ['nullable', 'string', 'max:150'],
            'designation'   => ['nullable', 'string', 'max:100'],
            'address'       => ['nullable', 'string', 'max:255'],
            'bio'           => ['nullable', 'string', 'max:2000'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
