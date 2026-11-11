<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\DTOs\Users\RoleData;
use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Role::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        // An HTML form with every box unticked sends no "permissions" key at all.
        if (! $this->has('permissions')) {
            $this->merge(['permissions' => []]);
        }

        if ($this->filled('name')) {
            $this->merge([
                'name' => Str::of((string) $this->input('name'))->trim()->lower()
                    ->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value(),
            ]);
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'min:3', 'max:60',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role instanceof Role ? $role->getKey() : null)],
            'permissions' => ['present', 'array'],
            // Only code-defined permissions can be granted.
            'permissions.*' => ['string', 'distinct', Rule::in(PermissionName::values())],
        ];
    }

    public function toDto(): RoleData
    {
        return RoleData::fromArray($this->validated());
    }
}
