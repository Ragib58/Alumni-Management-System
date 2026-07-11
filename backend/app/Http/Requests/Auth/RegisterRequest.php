<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'                 => ['nullable', 'string', 'max:30'],
            'password'              => ['required', 'confirmed', Password::defaults()],

            // Optional profile fields captured at sign-up.
            'student_id'            => ['nullable', 'string', 'max:50', 'unique:alumni_profiles,student_id'],
            'batch'                 => ['nullable', 'string', 'max:20'],
            'department'            => ['nullable', 'string', 'max:100'],
            'session'               => ['nullable', 'string', 'max:20'],
        ];
    }
}
