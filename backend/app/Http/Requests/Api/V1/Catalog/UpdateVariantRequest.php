<?php

namespace App\Http\Requests\Api\V1\Catalog;

use App\Http\Requests\Concerns\TranslatableRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVariantRequest extends FormRequest
{
    use TranslatableRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('variant')->product) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('sku')) {
            $this->merge(['sku' => strtoupper(trim((string) $this->input('sku')))]);
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:64', 'regex:/^[A-Z0-9\-_]+$/',
                Rule::unique('product_variants', 'sku')->ignore($this->route('variant')->getKey())],
            'base_price' => $this->moneyRules(),
        ];
    }

    public function sku(): string
    {
        return $this->validated('sku');
    }

    public function basePrice(): string
    {
        return number_format((float) $this->validated('base_price'), 2, '.', '');
    }
}
