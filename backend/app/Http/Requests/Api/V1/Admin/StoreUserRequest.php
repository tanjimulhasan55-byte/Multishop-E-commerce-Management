<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\DTOs\Users\UserData;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    protected function editedUserId(): ?int
    {
        $user = $this->route('user');

        return $user instanceof User ? $user->getKey() : null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $id = $this->editedUserId();

        return [
            'name' => ['required', 'string', 'max:120'],
            // Unique across soft-deleted rows too: a deleted account keeps its email reserved.
            'email' => ['required', 'string', 'email:rfc', 'max:191', Rule::unique('users', 'email')->ignore($id)],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9]{10,15}$/', Rule::unique('users', 'phone')->ignore($id)],
            'password' => [$id ? 'nullable' : 'required', 'string', 'confirmed', 'max:255', Password::defaults()],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ];
    }

    public function messages(): array
    {
        return ['roles.required' => __('Assign at least one role.')];
    }

    public function toDto(): UserData
    {
        return UserData::fromArray($this->validated());
    }
}
