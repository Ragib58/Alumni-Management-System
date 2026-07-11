<?php

namespace App\Http\Requests\User;

use App\Enums\RoleType;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized in controller via policy on the resolved model.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name'     => ['sometimes', 'required', 'string', 'max:255'],
            'email'    => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone'    => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'status'   => ['sometimes', 'required', Rule::in(UserStatus::values())],
            'roles'    => ['sometimes', 'array', 'min:1'],
            'roles.*'  => [Rule::in(RoleType::values())],
        ];
    }
}
