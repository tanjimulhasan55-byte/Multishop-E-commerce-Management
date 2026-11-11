<?php

namespace App\Http\Requests\Api\V1\Catalog;

use App\Http\Requests\Concerns\TranslatableRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pick option values (e.g. Size: S, M, XL + Color: Red, Blue) and every
 * combination becomes a variant with its own SKU.
 */
class GenerateVariantsRequest extends FormRequest
{
    use TranslatableRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('product')) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'attribute_value_ids' => ['required', 'array', 'min:1', 'max:60'],
            'attribute_value_ids.*' => ['integer', 'distinct', Rule::exists('attribute_values', 'id')],
            'base_price' => $this->moneyRules(false),
        ];
    }

    /** @return list<int> */
    public function valueIds(): array
    {
        return array_map('intval', $this->validated('attribute_value_ids'));
    }

    public function basePrice(): ?string
    {
        $price = $this->validated('base_price');

        return $price === null ? null : number_format((float) $price, 2, '.', '');
    }
}
