<?php

namespace App\Http\Requests\Api\V1\Shop;

/**
 * A shop owner changes the details, thumbnail, or price/stock of a listing
 * — for all variants at once (`price`, `stock`) or per variant (`variants[]`).
 */
class UpdateListingRequest extends StoreListingRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('listing')) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return $this->listingRules(defaultsRequired: false);
    }

    protected function productId(): int
    {
        return (int) $this->route('listing')->product_id;
    }
}
