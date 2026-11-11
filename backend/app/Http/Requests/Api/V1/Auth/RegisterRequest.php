<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\DTOs\Auth\RegisterData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9]{10,15}$/', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'confirmed', 'max:255', Password::defaults()],
        ];
    }

    public function toDto(): RegisterData
    {
        return new RegisterData(
            name: $this->string('name')->trim()->value(),
            email: $this->string('email')->trim()->lower()->value(),
            phone: $this->filled('phone') ? $this->string('phone')->trim()->value() : null,
            password: (string) $this->input('password'),
        );
    }
}
