<?php

namespace App\Http\Requests\Api\V1\Account;

use App\DTOs\Account\ProfileData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $id = $this->user()->getKey();

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', Rule::unique('users', 'email')->ignore($id)],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9]{10,15}$/', Rule::unique('users', 'phone')->ignore($id)],
        ];
    }

    public function toDto(): ProfileData
    {
        return new ProfileData(
            name: $this->string('name')->trim()->value(),
            email: $this->string('email')->trim()->lower()->value(),
            phone: $this->filled('phone') ? $this->string('phone')->trim()->value() : null,
        );
    }
}
