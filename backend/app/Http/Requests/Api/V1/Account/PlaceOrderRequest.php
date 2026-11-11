<?php

namespace App\Http\Requests\Api\V1\Account;

use App\DTOs\Orders\CheckoutData;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Order::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'integer', 'min:1'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            // Shop-variant id (listing `variants[].id`): the exact size/colour bought.
            'items.*.variant_id' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'shipping_address' => ['required', 'string', 'min:10', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function toDto(): CheckoutData
    {
        return CheckoutData::fromArray($this->validated());
    }
}
