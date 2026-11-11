<?php

namespace App\Http\Requests\Api\V1\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            // Verified against the JWT-authenticated user.
            'current_password' => ['required', 'string', 'current_password:api'],
            'password' => ['required', 'string', 'confirmed', 'different:current_password', 'max:255', Password::defaults()],
        ];
    }

    public function newPassword(): string
    {
        return (string) $this->validated('password');
    }
}
