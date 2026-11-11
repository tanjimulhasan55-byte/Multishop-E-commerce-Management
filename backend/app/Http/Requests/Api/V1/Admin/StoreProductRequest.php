<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\DTOs\Catalog\ProductData;
use App\Enums\ProductStatus;
use App\Http\Requests\Concerns\TranslatableRules;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating a catalog product. Used by admins (API + panel) and by shop
 * owners (their panel): ProductPolicy decides who may create / update.
 */
class StoreProductRequest extends FormRequest
{
    use TranslatableRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
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
        $product = $this->route('product');

        return [
            ...$this->translatable('name', true, 150),
            ...$this->translatable('description', false, 5000),
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'sku' => ['nullable', 'string', 'max:64', 'regex:/^[A-Z0-9\-_]+$/',
                Rule::unique('products', 'sku')->ignore($product instanceof Product ? $product->getKey() : null)],
            'base_price' => $this->moneyRules(),
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'thumbnail' => $this->imageRules(),
            'remove_thumbnail' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            ...$this->translatableAttributes('name', 'Product name'),
            ...$this->translatableAttributes('description', 'Description'),
            'category_id' => 'category',
            'base_price' => 'base price',
        ];
    }

    public function messages(): array
    {
        return ['sku.regex' => __('The SKU may only contain letters, numbers, dashes and underscores.')];
    }

    public function toDto(): ProductData
    {
        return ProductData::fromArray([...$this->validated(), 'thumbnail' => $this->file('thumbnail')]);
    }
}
