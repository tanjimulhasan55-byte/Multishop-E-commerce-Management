<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\DTOs\Shops\ShopData;
use App\Enums\RoleName;
use App\Enums\ShopStatus;
use App\Http\Requests\Concerns\TranslatableRules;
use App\Models\Shop;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared by the JSON API (admin) and the Blade admin panel.
 */
class StoreShopRequest extends FormRequest
{
    use TranslatableRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Shop::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            ...$this->translatable('name', true, 150),
            ...$this->translatable('description', false, 2000),
            'owner_id' => ['nullable', 'integer', $this->mustBeShopOwner()],
            'email' => ['nullable', 'email:rfc', 'max:191'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ShopStatus::class)],
            'logo' => $this->imageRules(),
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            ...$this->translatableAttributes('name', 'Shop name'),
            ...$this->translatableAttributes('description', 'Description'),
            'owner_id' => 'shop owner',
        ];
    }

    protected function mustBeShopOwner(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $isOwner = User::query()->whereKey($value)
                ->role(RoleName::ShopOwner->value)
                ->exists();

            if (! $isOwner) {
                $fail(__('The selected user is not a shop owner.'));
            }
        };
    }

    public function toDto(): ShopData
    {
        return ShopData::fromArray([...$this->validated(), 'logo' => $this->file('logo')]);
    }
}
