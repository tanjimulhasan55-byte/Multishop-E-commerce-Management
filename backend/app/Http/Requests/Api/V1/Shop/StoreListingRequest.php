<?php

namespace App\Http\Requests\Api\V1\Shop;

use App\DTOs\Listings\ListingData;
use App\Enums\ProductStatus;
use App\Http\Requests\Concerns\TranslatableRules;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A shop owner adds a catalog product to one of their shops.
 * `price`/`stock` apply to every variant; `variants[]` overrides individual
 * variants (e.g. XL costs more, only 2 left in Red).
 */
class StoreListingRequest extends FormRequest
{
    use TranslatableRules;

    public function authorize(): bool
    {
        return $this->user()?->can('manageListings', $this->route('shop')) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer',
                Rule::exists('products', 'id')
                    ->whereNull('deleted_at')
                    ->where('status', ProductStatus::Active->value),
                Rule::unique('shop_products', 'product_id')->where('shop_id', $this->route('shop')->getKey()),
            ],
            ...$this->listingRules(defaultsRequired: true),
        ];
    }

    /** @return array<string, array<int, mixed>> */
    protected function listingRules(bool $defaultsRequired): array
    {
        return [
            'price' => $this->moneyRules($defaultsRequired),
            'compare_at_price' => [...$this->moneyRules(false), 'gt:price'],
            'stock' => [$defaultsRequired ? 'required' : 'nullable', 'integer', 'min:0', 'max:1000000'],
            ...$this->translatable('title', false, 150),
            ...$this->translatable('description', false, 5000),
            'is_active' => ['sometimes', 'boolean'],
            'thumbnail' => $this->imageRules(),
            'remove_thumbnail' => ['sometimes', 'boolean'],

            'variants' => ['sometimes', 'array', 'max:100'],
            'variants.*.variant_id' => ['required', 'integer', 'distinct'],
            'variants.*.price' => $this->moneyRules(),
            'variants.*.compare_at_price' => [...$this->moneyRules(false), 'gt:variants.*.price'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'variants.*.is_active' => ['sometimes', 'boolean'],
        ];
    }

    /** The product whose variants are being priced. */
    protected function productId(): int
    {
        return (int) $this->input('product_id');
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $ids = collect($this->input('variants', []))->pluck('variant_id')->filter()->map(fn ($id) => (int) $id);

            if ($ids->isEmpty() || $validator->errors()->isNotEmpty()) {
                return;
            }

            $valid = ProductVariant::query()->where('product_id', $this->productId())->whereKey($ids)->count();

            if ($valid !== $ids->count()) {
                $validator->errors()->add('variants', __('One or more variants do not belong to this product.'));
            }
        }];
    }

    public function messages(): array
    {
        return [
            'product_id.unique' => __('This product is already listed in your shop.'),
            'product_id.exists' => __('Only active catalog products can be added.'),
            'compare_at_price.gt' => __('The compare-at price must be higher than the price.'),
            'variants.*.compare_at_price.gt' => __('The compare-at price must be higher than the price.'),
        ];
    }

    public function attributes(): array
    {
        return [
            ...$this->translatableAttributes('title', 'Shop title'),
            ...$this->translatableAttributes('description', 'Shop description'),
            'variants.*.price' => 'variant price',
            'variants.*.stock' => 'variant stock',
        ];
    }

    public function listedProductId(): int
    {
        return (int) $this->validated('product_id');
    }

    public function toDto(): ListingData
    {
        return ListingData::fromArray([...$this->validated(), 'thumbnail' => $this->file('thumbnail')]);
    }
}
