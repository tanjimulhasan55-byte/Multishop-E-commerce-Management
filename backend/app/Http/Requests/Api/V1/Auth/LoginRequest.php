<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\DTOs\Auth\LoginData;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:191'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function toDto(): LoginData
    {
        return new LoginData(
            email: strtolower($this->string('email')->trim()->value()),
            password: (string) $this->input('password'),
        );
    }
}
